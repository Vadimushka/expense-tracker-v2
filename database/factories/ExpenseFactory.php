<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'description' => fake()->sentence(3),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'due_date' => null,
            'category_id' => Category::factory(),
            'is_periodic' => false,
            'periodic_type' => null,
            'day_of_month' => null,
            'month_of_year' => null,
            'start_date' => null,
        ];
    }

    public function periodic(string $type = 'monthly'): static
    {
        return $this->state(fn () => [
            'is_periodic' => true,
            'periodic_type' => $type,
            'day_of_month' => fake()->numberBetween(1, 28),
            'month_of_year' => $type === 'yearly' ? fake()->numberBetween(1, 12) : null,
            'start_date' => now()->startOfMonth(),
            'date' => null,
        ]);
    }
}
