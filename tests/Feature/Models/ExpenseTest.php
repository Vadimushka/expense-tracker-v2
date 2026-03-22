<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->global()->create();
    $this->actingAs($this->user);
});

it('isolates expenses by user', function () {
    $otherUser = User::factory()->create();

    Expense::factory()->for($this->user)->create([
        'description' => 'My expense',
        'category_id' => $this->category->id,
    ]);
    Expense::factory()->create([
        'user_id' => $otherUser->id,
        'description' => 'Other expense',
        'category_id' => $this->category->id,
    ]);

    $expenses = Expense::pluck('description');
    expect($expenses)->toContain('My expense')
        ->and($expenses)->not->toContain('Other expense');
});

it('auto-sets user_id on create', function () {
    $expense = Expense::create([
        'amount' => 1000,
        'description' => 'Test',
        'date' => now(),
        'category_id' => $this->category->id,
        'is_periodic' => false,
    ]);

    expect($expense->user_id)->toBe($this->user->id);
});

describe('scopeForMonth', function () {
    it('returns monthly periodic expenses', function () {
        Expense::factory()->for($this->user)->periodic('monthly')->create([
            'description' => 'Monthly rent',
            'category_id' => $this->category->id,
            'day_of_month' => 15,
        ]);

        $found = Expense::forMonth(3, 2026)->pluck('description');
        expect($found)->toContain('Monthly rent');
    });

    it('returns yearly periodic expenses for matching month', function () {
        Expense::factory()->for($this->user)->periodic('yearly')->create([
            'description' => 'Annual insurance',
            'category_id' => $this->category->id,
            'month_of_year' => 6,
        ]);

        expect(Expense::forMonth(6, 2026)->pluck('description'))->toContain('Annual insurance')
            ->and(Expense::forMonth(7, 2026)->pluck('description'))->not->toContain('Annual insurance');
    });

    it('returns one-time expenses by date', function () {
        Expense::factory()->for($this->user)->create([
            'description' => 'One-time purchase',
            'category_id' => $this->category->id,
            'date' => '2026-03-15',
            'is_periodic' => false,
        ]);

        expect(Expense::forMonth(3, 2026)->pluck('description'))->toContain('One-time purchase')
            ->and(Expense::forMonth(4, 2026)->pluck('description'))->not->toContain('One-time purchase');
    });

    it('returns one-time expenses by due_date when date is null', function () {
        Expense::factory()->for($this->user)->create([
            'description' => 'Due date expense',
            'category_id' => $this->category->id,
            'date' => null,
            'due_date' => '2026-05-20',
            'is_periodic' => false,
        ]);

        expect(Expense::forMonth(5, 2026)->pluck('description'))->toContain('Due date expense')
            ->and(Expense::forMonth(6, 2026)->pluck('description'))->not->toContain('Due date expense');
    });
});
