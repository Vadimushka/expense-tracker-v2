<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseSplit;

class SplitService
{
    public function getAll(): array
    {
        $splits = ExpenseSplit::with('expense')->get()->groupBy('expense_id');

        $result = [];
        foreach ($splits as $expenseId => $items) {
            $expense = $items->first()->expense;
            $result[] = [
                'expense_id' => (int) $expenseId,
                'expense_description' => $expense?->description,
                'parts' => $items->map(fn ($s) => [
                    'income_type' => $s->income_type,
                    'percent' => $s->percent,
                ])->values(),
            ];
        }

        return $result;
    }

    public function save(int $expenseId, array $parts): void
    {
        ExpenseSplit::where('expense_id', $expenseId)->delete();

        foreach ($parts as $part) {
            ExpenseSplit::create([
                'expense_id' => $expenseId,
                'income_type' => $part['income_type'],
                'percent' => $part['percent'],
            ]);
        }
    }

    public function delete(Expense $expense): void
    {
        $expense->splits()->delete();
    }
}
