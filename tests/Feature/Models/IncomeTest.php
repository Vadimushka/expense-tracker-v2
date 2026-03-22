<?php

use App\Models\Income;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('isolates incomes by user', function () {
    $otherUser = User::factory()->create();

    Income::factory()->for($this->user)->salary()->create(['description' => 'My salary']);
    Income::factory()->create([
        'user_id' => $otherUser->id,
        'description' => 'Other salary',
    ]);

    $incomes = Income::pluck('description');
    expect($incomes)->toContain('My salary')
        ->and($incomes)->not->toContain('Other salary');
});

describe('scopeForMonth', function () {
    it('returns periodic incomes for any month', function () {
        Income::factory()->for($this->user)->periodic()->salary()->create([
            'description' => 'Monthly salary',
            'day_of_month' => 10,
        ]);

        expect(Income::forMonth(1, 2026)->pluck('description'))->toContain('Monthly salary')
            ->and(Income::forMonth(6, 2026)->pluck('description'))->toContain('Monthly salary');
    });

    it('returns one-time incomes only for correct month', function () {
        Income::factory()->for($this->user)->create([
            'description' => 'Bonus',
            'date' => '2026-03-15',
            'is_periodic' => false,
        ]);

        expect(Income::forMonth(3, 2026)->pluck('description'))->toContain('Bonus')
            ->and(Income::forMonth(4, 2026)->pluck('description'))->not->toContain('Bonus');
    });
});
