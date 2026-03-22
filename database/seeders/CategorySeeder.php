<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Еда', 'color' => '#ef4444', 'icon' => 'UtensilsCrossed'],
            ['name' => 'Транспорт', 'color' => '#3b82f6', 'icon' => 'Car'],
            ['name' => 'Жильё', 'color' => '#8b5cf6', 'icon' => 'Home'],
            ['name' => 'Развлечения', 'color' => '#f59e0b', 'icon' => 'Gamepad2'],
            ['name' => 'Здоровье', 'color' => '#10b981', 'icon' => 'Heart'],
            ['name' => 'Одежда', 'color' => '#ec4899', 'icon' => 'Shirt'],
            ['name' => 'Образование', 'color' => '#06b6d4', 'icon' => 'GraduationCap'],
            ['name' => 'Подписки', 'color' => '#6366f1', 'icon' => 'CreditCard'],
            ['name' => 'Прочее', 'color' => '#6b7280', 'icon' => 'MoreHorizontal'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name'], 'user_id' => null], $category);
        }
    }
}
