<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeleteStoreImageRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('store'));
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
            'confirmed.required' => 'Confirme a remoção da imagem da loja.',
            'confirmed.accepted' => 'Confirme a remoção da imagem da loja.',
        ];
    }
}
