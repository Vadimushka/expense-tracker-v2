<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseSplit;
use App\Models\User;
use App\Services\SplitService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->global()->create();
    $this->actingAs($this->user);
    $this->service = app(SplitService::class);
});

it('saves splits for expense', function () {
    $expense = Expense::factory()->for($this->user)->periodic()->create([
        'category_id' => $this->category->id,
    ]);

    $this->service->save($expense->id, [
        ['income_type' => 'salary', 'percent' => 60],
        ['income_type' => 'advance', 'percent' => 40],
    ]);

    expect(ExpenseSplit::where('expense_id', $expense->id)->count())->toBe(2);
});

it('replaces existing splits on save', function () {
    $expense = Expense::factory()->for($this->user)->periodic()->create([
        'category_id' => $this->category->id,
    ]);

    $this->service->save($expense->id, [
        ['income_type' => 'salary', 'percent' => 50],
        ['income_type' => 'advance', 'percent' => 50],
    ]);

    $this->service->save($expense->id, [
        ['income_type' => 'salary', 'percent' => 70],
        ['income_type' => 'advance', 'percent' => 30],
    ]);

    $splits = ExpenseSplit::where('expense_id', $expense->id)->get();
    expect($splits)->toHaveCount(2)
        ->and($splits->firstWhere('income_type', 'salary')->percent)->toBe('70.00');
});

it('deletes splits for expense', function () {
    $expense = Expense::factory()->for($this->user)->periodic()->create([
        'category_id' => $this->category->id,
    ]);

    $this->service->save($expense->id, [
        ['income_type' => 'salary', 'percent' => 60],
        ['income_type' => 'advance', 'percent' => 40],
    ]);

    $this->service->delete($expense);

    expect(ExpenseSplit::where('expense_id', $expense->id)->count())->toBe(0);
});

it('returns all splits grouped by expense', function () {
    $expense = Expense::factory()->for($this->user)->periodic()->create([
        'description' => 'Mortgage',
        'category_id' => $this->category->id,
    ]);

    $this->service->save($expense->id, [
        ['income_type' => 'salary', 'percent' => 60],
        ['income_type' => 'advance', 'percent' => 40],
    ]);

    $all = $this->service->getAll();

    expect($all)->toHaveCount(1)
        ->and($all[0]['expense_description'])->toBe('Mortgage')
        ->and($all[0]['parts'])->toHaveCount(2);
});
