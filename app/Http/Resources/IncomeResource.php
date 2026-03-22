<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d'),
            'is_periodic' => $this->is_periodic,
            'day_of_month' => $this->day_of_month,
            'created_at' => $this->created_at,
        ];
    }
}
