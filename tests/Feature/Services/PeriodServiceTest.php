<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\ExpenseSplit;
use App\Models\Income;
use App\Models\User;
use App\Services\PeriodService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->global()->create();
    $this->actingAs($this->user);
    $this->service = app(PeriodService::class);
});

describe('buildIncomeEntries', function () {
    it('returns periodic income with weekend adjustment', function () {
        Income::factory()->for($this->user)->periodic()->salary()->create([
            'day_of_month' => 10,
            'amount' => 100000,
        ]);

        $entries = $this->service->buildIncomeEntries(3, 2026);

        expect($entries)->toHaveCount(1)
            ->and($entries[0]['type'])->toBe('salary')
            ->and($entries[0]['amount'])->toBe('100000.00');
    });

    it('adjusts saturday to friday', function () {
        Income::factory()->for($this->user)->periodic()->salary()->create([
            'day_of_month' => 7, // 2026-03-07 is Saturday
            'amount' => 50000,
        ]);

        $entries = $this->service->buildIncomeEntries(3, 2026);

        expect($entries[0]['calculated_date'])->toBe('2026-03-06'); // Friday
    });

    it('adjusts sunday to friday', function () {
        Income::factory()->for($this->user)->periodic()->salary()->create([
            'day_of_month' => 8, // 2026-03-08 is Sunday
            'amount' => 50000,
        ]);

        $entries = $this->service->buildIncomeEntries(3, 2026);

        expect($entries[0]['calculated_date'])->toBe('2026-03-06'); // Friday
    });

    it('sorts entries by date', function () {
        Income::factory()->for($this->user)->periodic()->salary()->create([
            'day_of_month' => 25,
            'amount' => 100000,
        ]);
        Income::factory()->for($this->user)->periodic()->advance()->create([
            'day_of_month' => 10,
            'amount' => 50000,
        ]);

        $entries = $this->service->buildIncomeEntries(3, 2026);

        expect($entries)->toHaveCount(2)
            ->and($entries[0]['type'])->toBe('advance')
            ->and($entries[1]['type'])->toBe('salary');
    });
});

describe('buildExpenseEntries', function () {
    it('returns periodic expenses with calculated date', function () {
        Expense::factory()->for($this->user)->periodic()->create([
            'description' => 'Rent',
            'amount' => 30000,
            'day_of_month' => 1,
            'category_id' => $this->category->id,
        ]);

        $entries = $this->service->buildExpenseEntries(3, 2026);

        expect($entries)->toHaveCount(1)
            ->and($entries[0]['description'])->toBe('Rent')
            ->and($entries[0]['calculated_date'])->toBe('2026-03-01');
    });
});

describe('buildPeriods', function () {
    it('builds periods between incomes', function () {
        Income::factory()->for($this->user)->periodic()->advance()->create([
            'day_of_month' => 10,
            'amount' => 50000,
        ]);
        Income::factory()->for($this->user)->periodic()->salary()->create([
            'day_of_month' => 25,
            'amount' => 100000,
        ]);

        Expense::factory()->for($this->user)->periodic()->create([
            'amount' => 20000,
            'day_of_month' => 12,
            'category_id' => $this->category->id,
        ]);

        $periods = $this->service->buildPeriods(3, 2026);

        expect($periods)->toHaveCount(2)
            ->and($periods[0]['income']['type'])->toBe('advance')
            ->and($periods[1]['income']['type'])->toBe('salary');
    });

    it('handles no incomes gracefully', function () {
        Expense::factory()->for($this->user)->create([
            'amount' => 5000,
            'date' => '2026-03-15',
            'category_id' => $this->category->id,
        ]);

        $periods = $this->service->buildPeriods(3, 2026);

        expect($periods)->toHaveCount(1)
            ->and($periods[0]['income'])->toBeNull();
    });
});

describe('applyPaymentStatus', function () {
    it('marks paid expenses', function () {
        $expense = Expense::factory()->for($this->user)->periodic()->create([
            'category_id' => $this->category->id,
            'day_of_month' => 5,
        ]);

        ExpensePayment::factory()->for($this->user)->create([
            'expense_id' => $expense->id,
            'month' => 3,
            'year' => 2026,
        ]);

        $entries = $this->service->buildExpenseEntries(3, 2026);
        $entries = $this->service->applyPaymentStatus($entries, 3, 2026);

        expect($entries[0]['is_paid'])->toBeTrue();
    });
});

describe('applySplits', function () {
    it('splits expense by percent', function () {
        $expense = Expense::factory()->for($this->user)->periodic()->create([
            'description' => 'Mortgage',
            'amount' => 30000,
            'day_of_month' => 15,
            'category_id' => $this->category->id,
        ]);

        ExpenseSplit::factory()->for($this->user)->create([
            'expense_id' => $expense->id,
            'income_type' => 'salary',
            'percent' => 60,
        ]);
        ExpenseSplit::factory()->for($this->user)->create([
            'expense_id' => $expense->id,
            'income_type' => 'advance',
            'percent' => 40,
        ]);

        Income::factory()->for($this->user)->periodic()->salary()->create(['day_of_month' => 25, 'amount' => 100000]);
        Income::factory()->for($this->user)->periodic()->advance()->create(['day_of_month' => 10, 'amount' => 50000]);

        $incomeEntries = $this->service->buildIncomeEntries(3, 2026);
        $expenseEntries = $this->service->buildExpenseEntries(3, 2026);
        $result = $this->service->applySplits($expenseEntries, $incomeEntries);

        expect($result)->toHaveCount(2)
            ->and($result[0]['amount'])->toBe(18000.0) // 60%
            ->and($result[1]['amount'])->toBe(12000.0); // 40%
    });
});
