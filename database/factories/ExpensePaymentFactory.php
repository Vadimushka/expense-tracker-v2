<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpensePayment>
 */
class ExpensePaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_id' => Expense::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2024, 2026),
            'paid_at' => now(),
        ];
    }
}
