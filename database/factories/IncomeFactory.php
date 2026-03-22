<?php

namespace Database\Factories;

use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Income>
 */
class IncomeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 30000, 200000),
            'type' => fake()->randomElement(['salary', 'advance']),
            'description' => null,
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'is_periodic' => false,
            'day_of_month' => null,
        ];
    }

    public function periodic(): static
    {
        return $this->state(fn () => [
            'is_periodic' => true,
            'day_of_month' => fake()->numberBetween(1, 28),
            'date' => null,
        ]);
    }

    public function salary(): static
    {
        return $this->state(fn () => ['type' => 'salary']);
    }

    public function advance(): static
    {
        return $this->state(fn () => ['type' => 'advance']);
    }
}
