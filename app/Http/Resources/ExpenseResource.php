<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'is_periodic' => $this->is_periodic,
            'periodic_type' => $this->periodic_type,
            'day_of_month' => $this->day_of_month,
            'month_of_year' => $this->month_of_year,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'category_id' => $this->category_id,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'color' => $this->category->color,
                'icon' => $this->category->icon,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}
