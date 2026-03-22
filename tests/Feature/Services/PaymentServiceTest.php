<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Services\PaymentService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->global()->create();
    $this->actingAs($this->user);
    $this->service = app(PaymentService::class);
});

it('returns payment summary for month', function () {
    Expense::factory()->for($this->user)->periodic()->create([
        'description' => 'Rent',
        'amount' => 30000,
        'category_id' => $this->category->id,
    ]);

    $summary = $this->service->getSummary(3, 2026);

    expect($summary['items'])->toHaveCount(1)
        ->and($summary['total'])->toBe(30000.0)
        ->and($summary['unpaid'])->toBe(30000.0);
});

it('toggles payment on', function () {
    $expense = Expense::factory()->for($this->user)->periodic()->create([
        'category_id' => $this->category->id,
    ]);

    $result = $this->service->toggle($expense->id, 3, 2026);

    expect($result)->toBeTrue()
        ->and(ExpensePayment::where('expense_id', $expense->id)->exists())->toBeTrue();
});

it('toggles payment off', function () {
    $expense = Expense::factory()->for($this->user)->periodic()->create([
        'category_id' => $this->category->id,
    ]);

    ExpensePayment::factory()->for($this->user)->create([
        'expense_id' => $expense->id,
        'month' => 3,
        'year' => 2026,
    ]);

    $result = $this->service->toggle($expense->id, 3, 2026);

    expect($result)->toBeFalse()
        ->and(ExpensePayment::where('expense_id', $expense->id)->exists())->toBeFalse();
});
