<?php

namespace App\Http\Requests\Auth;

use App\Rules\BcryptPassword;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'password' => ['bail', 'required', 'string', new BcryptPassword, 'current_password:web'],
            'confirmation' => ['required', 'string', 'in:EXCLUIR'],
            'id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'store_id' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.required' => 'Informe sua senha atual.',
            'password.string' => 'Informe uma senha válida.',
            'password.current_password' => 'A senha atual está incorreta.',
            'confirmation.required' => 'Digite EXCLUIR para confirmar.',
            'confirmation.string' => 'Digite EXCLUIR para confirmar.',
            'confirmation.in' => 'Digite EXCLUIR para confirmar.',
            '*.prohibited' => 'A exclusão é permitida apenas para a conta autenticada.',
        ];
    }
}
