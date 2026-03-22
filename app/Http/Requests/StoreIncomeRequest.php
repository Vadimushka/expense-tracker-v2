<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:salary,advance',
            'description' => 'nullable|string|max:255',
            'is_periodic' => 'required|boolean',
            'day_of_month' => 'required_if:is_periodic,true|nullable|integer|min:1|max:31',
            'date' => 'required_if:is_periodic,false|nullable|date',
        ];
    }
}
