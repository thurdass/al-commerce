<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateProductStatusRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('product'));
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return ['active' => ['required', 'boolean']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'active.required' => 'Informe o status do produto.',
            'active.boolean' => 'Informe um status válido para o produto.',
        ];
    }
}
