<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'icon' => fake()->randomElement(['Home', 'Car', 'Heart', 'Shirt', 'CreditCard']),
        ];
    }

    public function global(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }
}
