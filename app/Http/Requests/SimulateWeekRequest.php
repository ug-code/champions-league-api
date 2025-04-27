<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulateWeekRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'week' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'week.required' => 'Hafta bilgisi zorunludur.',
            'week.integer'  => 'Hafta bir tam sayı olmalıdır.',
            'week.min'      => 'Hafta numarası en az 1 olmalıdır.',
        ];
    }
}
