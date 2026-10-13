<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteProductRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('delete', $this->route('product'));
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return ['confirmed' => ['required', 'accepted']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'confirmed.required' => 'Confirme a exclusão do produto.',
            'confirmed.accepted' => 'Confirme a exclusão do produto.',
        ];
    }
}
