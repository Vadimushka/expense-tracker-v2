<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'description', 'date', 'due_date',
        'category_id', 'is_periodic', 'periodic_type',
        'day_of_month', 'month_of_year', 'start_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'due_date' => 'date',
            'is_periodic' => 'boolean',
            'day_of_month' => 'integer',
            'month_of_year' => 'integer',
            'start_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('expenses.user_id', auth()->id());
            }
        });

        static::creating(function (Expense $expense) {
            if (auth()->check() && ! $expense->user_id) {
                $expense->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->where(function ($q) use ($month, $year) {
            $q->where(function ($q2) {
                $q2->where('is_periodic', true)->where('periodic_type', 'monthly');
            })
                ->orWhere(function ($q2) use ($month) {
                    $q2->where('is_periodic', true)
                        ->where('periodic_type', 'yearly')
                        ->where('month_of_year', $month);
                })
                ->orWhere(function ($q2) use ($month, $year) {
                    $q2->where('is_periodic', false)
                        ->where(function ($q3) use ($month, $year) {
                            $q3->where(function ($q4) use ($month, $year) {
                                $q4->whereMonth('date', $month)->whereYear('date', $year);
                            })->orWhere(function ($q4) use ($month, $year) {
                                $q4->whereNull('date')
                                    ->whereMonth('due_date', $month)
                                    ->whereYear('due_date', $year);
                            });
                        });
                });
        });
    }
}
