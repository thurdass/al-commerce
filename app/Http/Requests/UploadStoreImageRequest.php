<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadStoreImageRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('store'));
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return ['image' => ['bail', 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.Store::IMAGE_MAX_SIZE_KB]];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'image.required' => 'Selecione uma imagem para a loja.',
            'image.file' => 'Envie um arquivo de imagem válido.',
            'image.image' => 'Envie uma imagem JPEG, PNG ou WebP válida.',
            'image.mimes' => 'Envie uma imagem JPEG, PNG ou WebP válida.',
            'image.max' => 'A imagem deve ter no máximo 2 MB.',
            'image.uploaded' => 'Não foi possível enviar a imagem. O limite é 2 MB.',
        ];
    }
}
