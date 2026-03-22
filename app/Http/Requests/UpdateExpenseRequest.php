<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('expense')?->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'sometimes|string|max:255',
            'is_periodic' => 'sometimes|boolean',
            'periodic_type' => 'nullable|string|in:monthly,yearly',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'start_date' => 'nullable|date',
            'date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'category_id' => 'sometimes|exists:categories,id',
        ];
    }
}
