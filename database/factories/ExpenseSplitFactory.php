<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseSplit>
 */
class ExpenseSplitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'expense_id' => Expense::factory(),
            'income_type' => fake()->randomElement(['salary', 'advance']),
            'percent' => fake()->randomFloat(2, 10, 90),
        ];
    }
}
