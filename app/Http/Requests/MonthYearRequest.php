<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonthYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ];
    }
}
