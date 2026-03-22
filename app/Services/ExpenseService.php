<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseService
{
    public function getGrouped(): array
    {
        return Expense::with('category')
            ->selectRaw('
                description,
                category_id,
                is_periodic,
                periodic_type,
                day_of_month,
                month_of_year,
                COUNT(*) as payments_count,
                SUM(amount) as total_amount,
                MIN(COALESCE(date, due_date)) as first_date,
                MAX(COALESCE(date, due_date)) as last_date,
                array_agg(id ORDER BY COALESCE(date, due_date, created_at)) as expense_ids_raw
            ')
            ->groupBy('description', 'category_id', 'is_periodic', 'periodic_type', 'day_of_month', 'month_of_year')
            ->orderBy('description')
            ->get()
            ->map(function ($group) {
                $isPeriodic = (bool) $group->is_periodic;
                $idsRaw = trim($group->expense_ids_raw, '{}');
                $ids = $idsRaw ? array_map('intval', explode(',', $idsRaw)) : [];

                return [
                    'expense_ids' => $ids,
                    'description' => $group->description,
                    'category' => $group->category,
                    'is_periodic' => $isPeriodic,
                    'periodic_type' => $group->periodic_type,
                    'day_of_month' => $group->day_of_month,
                    'month_of_year' => $group->month_of_year,
                    'payments_count' => (int) $group->payments_count,
                    'total_amount' => $group->total_amount,
                    'monthly_amount' => $isPeriodic
                        ? $group->total_amount
                        : ($group->payments_count > 0
                            ? number_format((float) $group->total_amount / $group->payments_count, 2, '.', '')
                            : '0.00'),
                    'first_date' => $group->first_date,
                    'last_date' => $group->last_date,
                ];
            })
            ->toArray();
    }

    public function getByIds(array $ids)
    {
        return Expense::with('category')
            ->whereIn('id', $ids)
            ->orderBy('date')
            ->orderBy('due_date')
            ->get();
    }

    public function create(array $validated): Expense
    {
        if (($validated['is_periodic'] ?? false) && empty($validated['start_date'])) {
            $validated['start_date'] = now()->startOfMonth()->format('Y-m-d');
        }

        $expense = Expense::create($validated);
        $expense->load('category');

        return $expense;
    }
}
