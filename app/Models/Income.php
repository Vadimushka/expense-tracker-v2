<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'type', 'description',
        'date', 'is_periodic', 'day_of_month',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_periodic' => 'boolean',
            'day_of_month' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('incomes.user_id', auth()->id());
            }
        });

        static::creating(function (Income $income) {
            if (auth()->check() && ! $income->user_id) {
                $income->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForMonth(Builder $query, int $month, int $year): Builder
    {
        return $query->where(function ($q) use ($month, $year) {
            $q->where('is_periodic', true)
                ->orWhere(function ($q2) use ($month, $year) {
                    $q2->where('is_periodic', false)
                        ->whereMonth('date', $month)
                        ->whereYear('date', $year);
                });
        });
    }
}
