<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', Rule::in([
                'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
            ])],
            'ordenar' => ['nullable', 'string', Rule::in(['nome', 'recentes'])],
        ];
    }

    /** @return array<string, string> */
    public function filters(): array
    {
        return array_filter($this->validated(), fn ($value) => $value !== null && $value !== '');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('estado'))) {
            $this->merge(['estado' => strtoupper(trim($this->input('estado')))]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->url();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'q.string' => 'Informe um termo de busca válido.',
            'q.max' => 'A busca deve ter no máximo 255 caracteres.',
            'cidade.string' => 'Informe uma cidade válida.',
            'cidade.max' => 'A cidade deve ter no máximo 255 caracteres.',
            'estado.string' => 'Informe uma UF válida.',
            'estado.in' => 'Informe uma UF válida.',
            'ordenar.string' => 'Escolha uma ordenação válida.',
            'ordenar.in' => 'Escolha uma ordenação válida.',
        ];
    }
}
