<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Store::class);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'whatsapp' => ['required', 'string', 'regex:/^[1-9][0-9]{9,14}$/'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::in([
                'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
            ])],
            'address' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('state'))) {
            $this->merge(['state' => strtoupper(trim($this->input('state')))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da loja.',
            'name.string' => 'Informe um nome válido.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'description.required' => 'Informe a descrição da loja.',
            'description.string' => 'Informe uma descrição válida.',
            'description.max' => 'A descrição deve ter no máximo 5000 caracteres.',
            'whatsapp.required' => 'Informe o WhatsApp da loja.',
            'whatsapp.string' => 'Informe um número válido.',
            'whatsapp.regex' => 'Use de 10 a 15 dígitos, incluindo o código do país e o DDD.',
            'city.required' => 'Informe a cidade.',
            'city.string' => 'Informe uma cidade válida.',
            'city.max' => 'A cidade deve ter no máximo 255 caracteres.',
            'state.required' => 'Informe a UF.',
            'state.string' => 'Informe uma UF válida.',
            'state.in' => 'Informe uma UF válida.',
            'address.string' => 'Informe um endereço válido.',
            'address.max' => 'O endereço deve ter no máximo 255 caracteres.',
            'active.required' => 'Informe o status da loja.',
            'active.boolean' => 'Informe um status válido para a loja.',
        ];
    }
}
