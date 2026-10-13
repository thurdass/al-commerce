<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\BcryptPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed', new BcryptPassword],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome.',
            'name.string' => 'Informe um nome válido.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'email.required' => 'Informe seu e-mail.',
            'email.string' => 'Informe um e-mail válido.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe uma senha.',
            'password.string' => 'Informe uma senha válida.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no máximo 72 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
