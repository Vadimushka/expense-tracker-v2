<?php

namespace App\Services;

use App\Models\Expense;
use Carbon\Carbon;

class AdvisorService
{
    public function __construct(private PeriodService $periodService) {}

    public function getInsights(int $month, int $year): array
    {
        $periodsWithSplits = $this->periodService->buildRawPeriods($month, $year);
        $periodsRaw = $this->periodService->buildRawPeriodsWithoutSplits($month, $year);

        return [
            'period_balance' => $this->analyzePeriodBalance($periodsWithSplits),
            'optimization' => $this->analyzeOptimization($periodsRaw),
            'early_repayment' => $this->analyzeEarlyRepayment($month, $year),
        ];
    }

    private function analyzePeriodBalance(array $periods): array
    {
        $tips = [];

        if (count($periods) < 2) {
            return [
                'periods' => $periods,
                'tips' => ['Недостаточно периодов для анализа баланса.'],
            ];
        }

        $maxLoad = null;
        $minLoad = null;

        foreach ($periods as $i => $p) {
            if ($maxLoad === null || $p['load_percent'] > $periods[$maxLoad]['load_percent']) {
                $maxLoad = $i;
            }
            if ($minLoad === null || $p['load_percent'] < $periods[$minLoad]['load_percent']) {
                $minLoad = $i;
            }
        }

        $diff = $periods[$maxLoad]['load_percent'] - $periods[$minLoad]['load_percent'];

        if ($diff > 20) {
            $overloaded = $periods[$maxLoad];
            $underloaded = $periods[$minLoad];
            $overType = $overloaded['income']['type'] === 'salary' ? 'зарплаты' : 'аванса';
            $underType = $underloaded['income']['type'] === 'salary' ? 'зарплату' : 'аванс';

            $tips[] = "Период {$overType} нагружен на {$overloaded['load_percent']}%, а {$underType} — на {$underloaded['load_percent']}%. Разница {$diff}%.";

            $movable = array_filter($overloaded['expenses'], fn ($e) => $e['is_periodic'] && (float) $e['amount'] > 500);
            usort($movable, fn ($a, $b) => $b['amount'] - $a['amount']);

            $targetRemaining = $underloaded['remaining'];
            foreach (array_slice($movable, 0, 3) as $exp) {
                if ($exp['amount'] < $targetRemaining) {
                    $tips[] = "Перенесите «{$exp['description']}» ({$this->fmtRub($exp['amount'])}) на период {$underType} — это выровняет нагрузку.";
                    $targetRemaining -= $exp['amount'];
                }
            }
        } else {
            $tips[] = "Нагрузка между периодами сбалансирована (разница {$diff}%).";
        }

        return [
            'periods' => array_map(fn ($p) => [
                'type' => $p['income']['type'],
                'income' => $this->fmt($p['income']['amount']),
                'expenses' => $this->fmt($p['total_expenses']),
                'remaining' => $this->fmt($p['remaining']),
                'load_percent' => $p['load_percent'],
            ], $periods),
            'tips' => $tips,
        ];
    }

    private function analyzeOptimization(array $periods): array
    {
        $tips = [];
        $allExpenses = [];
        $totalIncome = 0;

        foreach ($periods as $p) {
            $totalIncome += $p['income']['amount'];
            foreach ($p['expenses'] as $e) {
                $allExpenses[] = $e;
            }
        }

        $totalExpenses = array_sum(array_column($allExpenses, 'amount'));
        $savingsRate = $totalIncome > 0 ? round(($totalIncome - $totalExpenses) / $totalIncome * 100, 1) : 0;

        if ($savingsRate < 10) {
            $tips[] = "Норма сбережений всего {$savingsRate}% — рекомендуется минимум 10-20%.";
        } elseif ($savingsRate >= 20) {
            $tips[] = "Отличная норма сбережений — {$savingsRate}%. Можно направить излишки на досрочное погашение ипотеки.";
        } else {
            $tips[] = "Норма сбережений {$savingsRate}% — в пределах нормы.";
        }

        $byCategory = [];
        foreach ($allExpenses as $e) {
            $cat = is_array($e['category']) ? $e['category']['name'] : ($e['category'] ?? 'Без категории');
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + (float) $e['amount'];
        }
        arsort($byCategory);

        $topCategories = array_slice($byCategory, 0, 3, true);
        foreach ($topCategories as $cat => $amount) {
            $percent = $totalIncome > 0 ? round($amount / $totalIncome * 100, 1) : 0;
            if ($percent > 30) {
                $tips[] = "«{$cat}» занимает {$percent}% от дохода ({$this->fmtRub($amount)}) — это существенная доля.";
            }
        }

        usort($allExpenses, fn ($a, $b) => $b['amount'] - $a['amount']);
        $biggest = array_slice($allExpenses, 0, 5);

        $byCategoryFormatted = [];
        foreach ($byCategory as $cat => $amount) {
            $byCategoryFormatted[$cat] = $this->fmt($amount);
        }

        return [
            'total_income' => $this->fmt($totalIncome),
            'total_expenses' => $this->fmt($totalExpenses),
            'savings_rate' => $savingsRate,
            'free_money' => $this->fmt($totalIncome - $totalExpenses),
            'by_category' => $byCategoryFormatted,
            'biggest_expenses' => array_map(fn ($e) => [
                'id' => $e['id'],
                'description' => $e['description'],
                'amount' => $this->fmt($e['amount']),
                'category' => is_array($e['category']) ? $e['category']['name'] : $e['category'],
                'is_periodic' => $e['is_periodic'],
            ], $biggest),
            'tips' => $tips,
        ];
    }

    private function analyzeEarlyRepayment(int $month, int $year): ?array
    {
        $mortgage = Expense::whereHas('category', fn ($q) => $q->where('name', 'Жильё'))
            ->orderByDesc('amount')
            ->first();

        if (! $mortgage) {
            $mortgage = Expense::where(function ($q) {
                $q->where('description', 'like', '%потек%')
                    ->orWhere('description', 'like', '%ипотек%')
                    ->orWhere('description', 'like', '%кредит%');
            })->orderByDesc('amount')->first();
        }

        if (! $mortgage) {
            return null;
        }

        $description = $mortgage->description;
        $payments = Expense::where('description', $description)
            ->where('is_periodic', false)
            ->where(function ($q) use ($month, $year) {
                $currentDate = Carbon::create($year, $month, 1);
                $q->where('date', '>=', $currentDate)
                    ->orWhere(fn ($q2) => $q2->whereNull('date')->where('due_date', '>=', $currentDate));
            })
            ->orderBy('date')->orderBy('due_date')
            ->get();

        if ($payments->isEmpty()) {
            $periodicMortgage = Expense::where('description', $description)->where('is_periodic', true)->first();
            if ($periodicMortgage) {
                $monthlyPayment = (float) $periodicMortgage->amount;
                $totalMonths = 336;
            } else {
                return null;
            }
        } else {
            $monthlyPayment = round($payments->take(12)->avg('amount'), 2);
            $totalMonths = $payments->count();
        }

        $scenarios = [];
        $scenarios[] = [
            'name' => 'Текущий график',
            'extra_monthly' => '0.00',
            'total_months' => $totalMonths,
            'total_paid' => $this->fmt($monthlyPayment * $totalMonths),
        ];

        foreach ([5000, 10000, 20000, 50000] as $extra) {
            if ($extra < $monthlyPayment * 2) {
                $effectiveMonthly = $monthlyPayment + $extra;
                $ratio = $monthlyPayment / $effectiveMonthly;
                $newMonths = (int) ceil($totalMonths * $ratio);
                $saved = ($totalMonths - $newMonths) * $monthlyPayment;

                $scenarios[] = [
                    'name' => "+{$this->fmtRub($extra)}/мес",
                    'extra_monthly' => $this->fmt($extra),
                    'total_months' => $newMonths,
                    'total_paid' => $this->fmt($effectiveMonthly * $newMonths),
                    'months_saved' => $totalMonths - $newMonths,
                    'money_saved' => $this->fmt($saved),
                ];
            }
        }

        return [
            'mortgage_name' => $mortgage->description,
            'monthly_payment' => $this->fmt($monthlyPayment),
            'remaining_payments' => $totalMonths,
            'scenarios' => $scenarios,
            'tips' => [
                'Даже небольшие доплаты значительно сокращают срок и переплату.',
                'Лучше вносить досрочно в первые годы — тогда экономия на процентах максимальна.',
                'Уточните в банке: сокращение срока выгоднее, чем снижение платежа.',
            ],
        ];
    }

    private function fmt(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function fmtRub(float $amount): string
    {
        return number_format($amount, 0, ',', ' ').' ₽';
    }
}
