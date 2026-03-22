<?php

namespace App\Services;

use App\Models\Income;

class IncomeService
{
    public function getGrouped(): array
    {
        return Income::selectRaw('
                type,
                description,
                is_periodic,
                day_of_month,
                COUNT(*) as payments_count,
                SUM(amount) as total_amount,
                MIN(date) as first_date,
                MAX(date) as last_date
            ')
            ->groupBy('type', 'description', 'is_periodic', 'day_of_month')
            ->orderBy('type')
            ->orderBy('description')
            ->get()
            ->map(fn ($group) => [
                'type' => $group->type,
                'description' => $group->description,
                'is_periodic' => (bool) $group->is_periodic,
                'day_of_month' => $group->day_of_month,
                'payments_count' => (int) $group->payments_count,
                'total_amount' => $group->total_amount,
                'first_date' => $group->first_date,
                'last_date' => $group->last_date,
            ])
            ->toArray();
    }

    public function getByKey(string $type, bool $isPeriodic, ?string $description = null)
    {
        $query = Income::where('type', $type)->where('is_periodic', $isPeriodic);

        if ($description !== null) {
            $query->where('description', $description);
        } else {
            $query->whereNull('description');
        }

        return $query->orderBy('date')->orderBy('day_of_month')->get();
    }
}
