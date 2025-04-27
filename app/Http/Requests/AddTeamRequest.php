<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gerekirse yetkilendirme eklenebilir
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'power' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages()
    {
        return [
            'name.required'  => 'Takım adı zorunludur.',
            'name.string'    => 'Takım adı metin olmalıdır.',
            'name.max'       => 'Takım adı en fazla 255 karakter olmalıdır.',
            'power.required' => 'Güç bilgisi zorunludur.',
            'power.integer'  => 'Güç bir tam sayı olmalıdır.',
            'power.min'      => 'Güç en az 1 olmalıdır.',
            'power.max'      => 'Güç en fazla 100 olmalıdır.',
        ];
    }
}
