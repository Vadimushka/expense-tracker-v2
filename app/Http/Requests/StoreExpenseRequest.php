<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'is_periodic' => 'required|boolean',
            'periodic_type' => 'nullable|string|in:monthly,yearly',
            'day_of_month' => 'required_if:is_periodic,true|nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
            'start_date' => 'nullable|date',
            'date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'category_id' => 'required|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Укажите хотя бы одну дату.',
        ];
    }
}
