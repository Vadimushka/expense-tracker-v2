<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getYearlyAnalytics(int $year): array
    {
        $oneTimeByMonth = $this->getOneTimeExpensesByMonth($year);
        $monthlyPeriodic = Expense::where('is_periodic', true)->where('periodic_type', 'monthly')->get();
        $yearlyPeriodic = Expense::where('is_periodic', true)->where('periodic_type', 'yearly')->get();

        $byMonth = $this->buildExpensesByMonth($year, $oneTimeByMonth, $monthlyPeriodic, $yearlyPeriodic);
        $byCategory = $this->buildExpensesByCategory($year, $monthlyPeriodic, $yearlyPeriodic);
        $yearTotal = $byMonth->sum(fn ($m) => (float) $m['total']);

        $incomeByMonth = $this->buildIncomeByMonth($year);
        $incomeYearTotal = $incomeByMonth->sum(fn ($m) => (float) $m['total']);

        return [
            'by_month' => $byMonth->values(),
            'by_category' => $byCategory->values(),
            'year_total' => number_format($yearTotal, 2, '.', ''),
            'income_by_month' => $incomeByMonth->values(),
            'income_year_total' => number_format($incomeYearTotal, 2, '.', ''),
        ];
    }

    private function getOneTimeExpensesByMonth(int $year)
    {
        return Expense::where('is_periodic', false)
            ->where(function ($q) use ($year) {
                $q->whereYear('date', $year)
                    ->orWhere(fn ($q2) => $q2->whereNull('date')->whereYear('due_date', $year));
            })
            ->select(
                DB::raw('EXTRACT(MONTH FROM COALESCE(date, due_date))::integer as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('EXTRACT(MONTH FROM COALESCE(date, due_date))'))
            ->get()
            ->keyBy('month');
    }

    private function buildExpensesByMonth(int $year, $oneTimeByMonth, $monthlyPeriodic, $yearlyPeriodic)
    {
        $byMonth = collect();

        for ($m = 1; $m <= 12; $m++) {
            $monthDate = Carbon::create($year, $m, 1);

            $monthlyTotal = $monthlyPeriodic
                ->filter(fn ($e) => ! $e->start_date || $e->start_date->startOfMonth()->lte($monthDate))
                ->sum('amount');

            $yearlyTotal = $yearlyPeriodic
                ->filter(fn ($e) => $e->month_of_year === $m
                    && (! $e->start_date || $e->start_date->startOfMonth()->lte($monthDate)))
                ->sum('amount');

            $total = (float) $monthlyTotal + ($oneTimeByMonth->get($m)?->total ?? 0) + (float) $yearlyTotal;

            if ($total > 0) {
                $byMonth->push(['month' => $m, 'total' => number_format($total, 2, '.', '')]);
            }
        }

        return $byMonth;
    }

    private function buildExpensesByCategory(int $year, $monthlyPeriodic, $yearlyPeriodic)
    {
        $oneTimeByCat = Expense::where('is_periodic', false)
            ->where(function ($q) use ($year) {
                $q->whereYear('date', $year)
                    ->orWhere(fn ($q2) => $q2->whereNull('date')->whereYear('due_date', $year));
            })
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $categoryTotals = [];
        foreach ($oneTimeByCat as $catId => $row) {
            $categoryTotals[$catId] = (float) $row->total;
        }

        $yearStart = Carbon::create($year, 1, 1);

        foreach ($monthlyPeriodic as $exp) {
            $start = $exp->start_date ? $exp->start_date->startOfMonth() : $yearStart;
            if ($start->year > $year) {
                continue;
            }
            $firstMonth = $start->year < $year ? 1 : $start->month;
            $activeMonths = 12 - $firstMonth + 1;
            $categoryTotals[$exp->category_id] = ($categoryTotals[$exp->category_id] ?? 0)
                + (float) $exp->amount * $activeMonths;
        }

        foreach ($yearlyPeriodic as $exp) {
            $start = $exp->start_date ? $exp->start_date->startOfMonth() : $yearStart;
            $expMonth = Carbon::create($year, $exp->month_of_year, 1);
            if ($expMonth->gte($start)) {
                $categoryTotals[$exp->category_id] = ($categoryTotals[$exp->category_id] ?? 0)
                    + (float) $exp->amount;
            }
        }

        $byCategory = collect();
        if (! empty($categoryTotals)) {
            $categories = DB::table('categories')->whereIn('id', array_keys($categoryTotals))->get()->keyBy('id');
            arsort($categoryTotals);
            foreach ($categoryTotals as $catId => $total) {
                $cat = $categories->get($catId);
                if ($cat) {
                    $byCategory->push([
                        'category_id' => $catId,
                        'category_name' => $cat->name,
                        'category_color' => $cat->color,
                        'total' => number_format($total, 2, '.', ''),
                    ]);
                }
            }
        }

        return $byCategory;
    }

    private function buildIncomeByMonth(int $year)
    {
        $oneTimeByMonth = Income::where('is_periodic', false)
            ->whereYear('date', $year)
            ->select(
                DB::raw('EXTRACT(MONTH FROM date)::integer as month'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy(DB::raw('EXTRACT(MONTH FROM date)'))
            ->get()
            ->keyBy('month');

        $monthlyPeriodicIncome = Income::where('is_periodic', true)->sum('amount');

        $incomeByMonth = collect();
        for ($m = 1; $m <= 12; $m++) {
            $total = $monthlyPeriodicIncome + ($oneTimeByMonth->get($m)?->total ?? 0);
            if ($total > 0) {
                $incomeByMonth->push(['month' => $m, 'total' => number_format($total, 2, '.', '')]);
            }
        }

        return $incomeByMonth;
    }
}
