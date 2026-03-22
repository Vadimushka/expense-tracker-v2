<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpensePayment;

class PaymentService
{
    public function getSummary(int $month, int $year): array
    {
        $allExpenses = Expense::with('category')
            ->forMonth($month, $year)
            ->orderBy('description')
            ->get();

        $payments = ExpensePayment::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('expense_id');

        $items = [];

        foreach ($allExpenses as $exp) {
            $paid = $payments->get($exp->id);
            $items[] = [
                'expense_id' => $exp->id,
                'expense_description' => $exp->description,
                'amount' => $exp->amount,
                'category' => $exp->category?->name,
                'category_color' => $exp->category?->color,
                'is_periodic' => $exp->is_periodic,
                'periodic_type' => $exp->periodic_type,
                'is_paid' => (bool) $paid,
                'paid_at' => $paid?->paid_at?->format('Y-m-d'),
            ];
        }

        $totalAmount = array_sum(array_column($items, 'amount'));
        $paidAmount = array_sum(array_map(fn ($i) => $i['is_paid'] ? (float) $i['amount'] : 0, $items));

        return [
            'month' => $month,
            'year' => $year,
            'items' => $items,
            'total' => $totalAmount,
            'paid' => $paidAmount,
            'unpaid' => $totalAmount - $paidAmount,
        ];
    }

    public function toggle(int $expenseId, int $month, int $year): bool
    {
        $existing = ExpensePayment::where('expense_id', $expenseId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        ExpensePayment::create([
            'expense_id' => $expenseId,
            'month' => $month,
            'year' => $year,
            'paid_at' => now(),
        ]);

        return true;
    }
}
