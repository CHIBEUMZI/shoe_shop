<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'avatar' => ['nullable', 'string', 'max:2048'],

            'role' => [
                'required',
                Rule::in(['admin', 'customer']),
            ],

            'is_active' => ['required', 'boolean'],
        ];
    }
}