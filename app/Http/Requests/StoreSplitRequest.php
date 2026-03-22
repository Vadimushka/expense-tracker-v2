<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSplitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_id' => 'required|exists:expenses,id',
            'parts' => 'required|array|min:2',
            'parts.*.income_type' => 'required|string|in:salary,advance',
            'parts.*.percent' => 'required|numeric|min:0|max:100',
        ];
    }
}
