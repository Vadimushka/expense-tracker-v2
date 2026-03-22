<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpensePayment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'expense_id', 'month', 'year', 'paid_at'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'month' => 'integer',
            'year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('expense_payments.user_id', auth()->id());
            }
        });

        static::creating(function (ExpensePayment $payment) {
            if (auth()->check() && ! $payment->user_id) {
                $payment->user_id = auth()->id();
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
