<?php

namespace App\Http\Requests;

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $userId = auth()->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($userId) // Ignore l'utilisateur actuel
            ],
            'phone' => [
                'required',
                'string',
                Rule::unique('users')->ignore($userId)
            ],
            'country_id' => ['required', 'exists:countries,id'],
        ];
    }
}
