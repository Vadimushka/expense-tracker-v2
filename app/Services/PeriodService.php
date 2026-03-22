<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\ExpenseSplit;
use App\Models\Income;
use Carbon\Carbon;

class PeriodService
{
    public function buildIncomeEntries(int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $entries = [];

        foreach (Income::where('is_periodic', true)->get() as $income) {
            $day = min($income->day_of_month, $daysInMonth);
            $date = $this->adjustForWeekend(Carbon::create($year, $month, $day));

            $entries[] = [
                'id' => $income->id,
                'amount' => $income->amount,
                'type' => $income->type,
                'description' => $income->description,
                'is_periodic' => true,
                'day_of_month' => $income->day_of_month,
                'calculated_date' => $date->format('Y-m-d'),
            ];
        }

        foreach (Income::forMonth($month, $year)->where('is_periodic', false)->get() as $income) {
            $entries[] = [
                'id' => $income->id,
                'amount' => $income->amount,
                'type' => $income->type,
                'description' => $income->description,
                'is_periodic' => false,
                'day_of_month' => null,
                'calculated_date' => $income->date->format('Y-m-d'),
            ];
        }

        usort($entries, fn ($a, $b) => $a['calculated_date'] <=> $b['calculated_date']);

        return $entries;
    }

    public function buildExpenseEntries(int $month, int $year): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $entries = [];

        foreach (Expense::with('category')->forMonth($month, $year)->get() as $expense) {
            if ($expense->is_periodic) {
                $day = min($expense->day_of_month, $daysInMonth);
                $date = Carbon::create($year, $month, $day)->format('Y-m-d');
            } else {
                $date = ($expense->date ?? $expense->due_date)->format('Y-m-d');
            }

            $entries[] = [
                'id' => $expense->id,
                'amount' => $expense->amount,
                'description' => $expense->description,
                'category' => $expense->category ? [
                    'id' => $expense->category->id,
                    'name' => $expense->category->name,
                    'color' => $expense->category->color,
                    'icon' => $expense->category->icon,
                ] : null,
                'is_periodic' => $expense->is_periodic,
                'day_of_month' => $expense->day_of_month,
                'calculated_date' => $date,
            ];
        }

        usort($entries, fn ($a, $b) => $a['calculated_date'] <=> $b['calculated_date']);

        return $entries;
    }

    public function applySplits(array $expenseEntries, array $incomeEntries): array
    {
        $allSplits = ExpenseSplit::with('expense')->get();

        if ($allSplits->isEmpty()) {
            return $expenseEntries;
        }

        $splitsByDescription = [];
        foreach ($allSplits as $split) {
            $desc = $split->expense?->description;
            if ($desc) {
                $splitsByDescription[$desc][] = $split;
            }
        }

        if (empty($splitsByDescription)) {
            return $expenseEntries;
        }

        $incomeTypeMap = [];
        foreach ($incomeEntries as $entry) {
            $incomeTypeMap[$entry['type']] = $entry['calculated_date'];
        }

        $result = [];
        foreach ($expenseEntries as $entry) {
            $desc = $entry['description'];
            if (isset($splitsByDescription[$desc])) {
                $totalAmount = (float) $entry['amount'];
                foreach ($splitsByDescription[$desc] as $split) {
                    $splitEntry = $entry;
                    $splitEntry['amount'] = round($totalAmount * (float) $split->percent / 100, 2);
                    $splitEntry['description'] = $desc.' (часть)';
                    if (isset($incomeTypeMap[$split->income_type])) {
                        $splitEntry['calculated_date'] = $incomeTypeMap[$split->income_type];
                    }
                    $result[] = $splitEntry;
                }
            } else {
                $result[] = $entry;
            }
        }

        return $result;
    }

    public function applyPaymentStatus(array $expenseEntries, int $month, int $year): array
    {
        $payments = ExpensePayment::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('expense_id');

        foreach ($expenseEntries as &$entry) {
            $paid = $payments->get($entry['id']);
            $entry['is_paid'] = (bool) $paid;
            $entry['paid_at'] = $paid?->paid_at?->format('Y-m-d');
        }
        unset($entry);

        return $expenseEntries;
    }

    public function buildPeriods(int $month, int $year): array
    {
        $incomeEntries = $this->buildIncomeEntries($month, $year);
        $expenseEntries = $this->buildExpenseEntries($month, $year);
        $expenseEntries = $this->applySplits($expenseEntries, $incomeEntries);
        $expenseEntries = $this->applyPaymentStatus($expenseEntries, $month, $year);

        $periods = [];

        if (empty($incomeEntries)) {
            $totalExpenses = array_sum(array_column($expenseEntries, 'amount'));
            $paidTotal = $this->sumPaid($expenseEntries);
            $unpaidTotal = $totalExpenses - $paidTotal;
            $periods[] = [
                'income' => null,
                'expenses' => $expenseEntries,
                'total_expenses' => $this->fmt($totalExpenses),
                'paid_total' => $this->fmt($paidTotal),
                'unpaid_total' => $this->fmt($unpaidTotal),
                'remaining' => $this->fmt(-$totalExpenses),
            ];
        } else {
            for ($i = 0; $i < count($incomeEntries); $i++) {
                $from = $incomeEntries[$i]['calculated_date'];
                $to = $incomeEntries[$i + 1]['calculated_date'] ?? null;

                $periodExpenses = array_values(array_filter($expenseEntries, function ($e) use ($from, $to) {
                    $d = $e['calculated_date'];

                    return $to ? ($d >= $from && $d < $to) : ($d >= $from);
                }));

                $totalExpenses = array_sum(array_column($periodExpenses, 'amount'));
                $paidTotal = $this->sumPaid($periodExpenses);
                $unpaidTotal = $totalExpenses - $paidTotal;
                $remaining = (float) $incomeEntries[$i]['amount'] - $unpaidTotal;

                $periods[] = [
                    'income' => $incomeEntries[$i],
                    'expenses' => $periodExpenses,
                    'total_expenses' => $this->fmt($totalExpenses),
                    'paid_total' => $this->fmt($paidTotal),
                    'unpaid_total' => $this->fmt($unpaidTotal),
                    'remaining' => $this->fmt($remaining),
                ];
            }

            $firstDate = $incomeEntries[0]['calculated_date'];
            $before = array_values(array_filter($expenseEntries, fn ($e) => $e['calculated_date'] < $firstDate));
            if (! empty($before)) {
                $totalBefore = array_sum(array_column($before, 'amount'));
                $paidBefore = $this->sumPaid($before);
                array_unshift($periods, [
                    'income' => null,
                    'expenses' => $before,
                    'total_expenses' => $this->fmt($totalBefore),
                    'paid_total' => $this->fmt($paidBefore),
                    'unpaid_total' => $this->fmt($totalBefore - $paidBefore),
                    'remaining' => $this->fmt(-$totalBefore),
                ]);
            }
        }

        return $periods;
    }

    public function buildRawPeriodsWithoutSplits(int $month, int $year): array
    {
        return $this->buildRawPeriodsInternal($month, $year, false);
    }

    public function buildRawPeriods(int $month, int $year): array
    {
        return $this->buildRawPeriodsInternal($month, $year, true);
    }

    private function buildRawPeriodsInternal(int $month, int $year, bool $withSplits): array
    {
        $incomeEntries = $this->buildIncomeEntries($month, $year);
        $expenseEntries = $this->buildExpenseEntries($month, $year);
        if ($withSplits) {
            $expenseEntries = $this->applySplits($expenseEntries, $incomeEntries);
        }

        $periods = [];

        for ($i = 0; $i < count($incomeEntries); $i++) {
            $from = $incomeEntries[$i]['calculated_date'];
            $to = $incomeEntries[$i + 1]['calculated_date'] ?? null;

            $periodExpenses = array_values(array_filter($expenseEntries, function ($e) use ($from, $to) {
                $d = $e['calculated_date'];

                return $to ? ($d >= $from && $d < $to) : ($d >= $from);
            }));

            $totalExpenses = array_sum(array_column($periodExpenses, 'amount'));

            $periods[] = [
                'income' => $incomeEntries[$i],
                'expenses' => $periodExpenses,
                'total_expenses' => $totalExpenses,
                'remaining' => (float) $incomeEntries[$i]['amount'] - $totalExpenses,
                'load_percent' => $incomeEntries[$i]['amount'] > 0
                    ? round($totalExpenses / (float) $incomeEntries[$i]['amount'] * 100, 1)
                    : 0,
            ];
        }

        return $periods;
    }

    private function adjustForWeekend(Carbon $date): Carbon
    {
        if ($date->isSaturday()) {
            $date->subDay();
        } elseif ($date->isSunday()) {
            $date->subDays(2);
        }

        return $date;
    }

    private function sumPaid(array $expenses): float
    {
        return array_sum(array_map(fn ($e) => ! empty($e['is_paid']) ? (float) $e['amount'] : 0, $expenses));
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
