<?php

use App\Http\Controllers\AdvisorController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SplitController;
use App\Http\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/dashboard', '/summary');

    Route::get('/summary', SummaryController::class)->name('summary');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::get('/expenses/grouped', [ExpenseController::class, 'grouped'])->name('expenses.grouped');
    Route::get('/expenses/by-ids', [ExpenseController::class, 'byIds'])->name('expenses.by-ids');

    Route::get('/incomes', [IncomeController::class, 'index'])->name('incomes.index');
    Route::post('/incomes', [IncomeController::class, 'store'])->name('incomes.store');
    Route::put('/incomes/{income}', [IncomeController::class, 'update'])->name('incomes.update');
    Route::delete('/incomes/{income}', [IncomeController::class, 'destroy'])->name('incomes.destroy');
    Route::get('/incomes/grouped', [IncomeController::class, 'grouped'])->name('incomes.grouped');
    Route::get('/incomes/by-key', [IncomeController::class, 'byKey'])->name('incomes.by-key');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/toggle', [PaymentController::class, 'toggle'])->name('payments.toggle');

    Route::get('/advisor', [AdvisorController::class, 'index'])->name('advisor.index');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');

    Route::get('/splits', [SplitController::class, 'index'])->name('splits.index');
    Route::post('/splits', [SplitController::class, 'store'])->name('splits.store');
    Route::delete('/splits/{expense}', [SplitController::class, 'destroy'])->name('splits.destroy');
});

require __DIR__.'/settings.php';
