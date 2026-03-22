<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use App\Services\ExpenseService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->global()->create();
    $this->actingAs($this->user);
    $this->service = app(ExpenseService::class);
});

it('creates expense with auto start_date for periodic', function () {
    $expense = $this->service->create([
        'amount' => 5000,
        'description' => 'Subscription',
        'is_periodic' => true,
        'periodic_type' => 'monthly',
        'day_of_month' => 15,
        'category_id' => $this->category->id,
    ]);

    expect($expense->start_date)->not->toBeNull()
        ->and($expense->user_id)->toBe($this->user->id)
        ->and($expense->category)->not->toBeNull();
});

it('groups expenses by description', function () {
    Expense::factory()->for($this->user)->periodic()->create([
        'description' => 'Netflix',
        'category_id' => $this->category->id,
        'amount' => 799,
    ]);

    $grouped = $this->service->getGrouped();

    expect($grouped)->toHaveCount(1)
        ->and($grouped[0]['description'])->toBe('Netflix');
});

it('returns expenses by ids', function () {
    $e1 = Expense::factory()->for($this->user)->create([
        'description' => 'First',
        'category_id' => $this->category->id,
    ]);
    $e2 = Expense::factory()->for($this->user)->create([
        'description' => 'Second',
        'category_id' => $this->category->id,
    ]);

    $result = $this->service->getByIds([$e1->id, $e2->id]);

    expect($result)->toHaveCount(2);
});
