<?php

namespace App\Http\Requests;

use App\Models\ProductImage;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadProductImagesRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('product'));
    }

    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.ProductImage::MAX_PER_UPLOAD],
            'images.*' => ['bail', 'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.ProductImage::MAX_SIZE_KB],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'images.required' => 'Selecione ao menos uma imagem.',
            'images.array' => 'Envie as imagens em uma lista de arquivos.',
            'images.min' => 'Selecione ao menos uma imagem.',
            'images.max' => 'Envie no máximo 3 imagens por vez.',
            'images.*.required' => 'Selecione um arquivo de imagem.',
            'images.*.file' => 'Envie um arquivo de imagem válido.',
            'images.*.uploaded' => 'Não foi possível enviar a imagem. O limite por arquivo é 2 MB.',
            'images.*.image' => 'Envie uma imagem JPEG, PNG ou WebP válida.',
            'images.*.mimes' => 'Envie uma imagem JPEG, PNG ou WebP válida.',
            'images.*.max' => 'Cada imagem deve ter no máximo 2 MB.',
        ];
    }
}
