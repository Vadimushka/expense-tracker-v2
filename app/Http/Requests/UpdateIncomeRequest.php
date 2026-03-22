<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('income')?->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'amount' => 'sometimes|numeric|min:0.01',
            'type' => 'sometimes|in:salary,advance',
            'description' => 'nullable|string|max:255',
            'is_periodic' => 'sometimes|boolean',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'date' => 'nullable|date',
        ];
    }
}
