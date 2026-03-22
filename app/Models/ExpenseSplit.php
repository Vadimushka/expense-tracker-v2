<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseSplit extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'expense_id', 'income_type', 'percent'];

    protected function casts(): array
    {
        return [
            'percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('expense_splits.user_id', auth()->id());
            }
        });

        static::creating(function (ExpenseSplit $split) {
            if (auth()->check() && ! $split->user_id) {
                $split->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
