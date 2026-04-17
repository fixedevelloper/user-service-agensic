<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'old_password' => ['required', 'current_password'], // Erreur si l'ancien mdp est faux
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.current_password' => "L'ancien mot de passe est incorrect.",
            'password.confirmed' => "La confirmation du nouveau mot de passe ne correspond pas.",
            'password.min' => "Le nouveau mot de passe doit faire au moins 8 caractères.",
        ];
    }
}
