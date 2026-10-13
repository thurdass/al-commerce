<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\ProductAvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ProductRequest extends FormRequest
{
    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'category_id' => ['bail', 'nullable', 'integer', Rule::exists(Category::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'stock_quantity' => ['bail', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'availability_status' => ['required', Rule::enum(ProductAvailabilityStatus::class)],
            'active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'category_id.integer' => 'Selecione uma categoria válida.',
            'category_id.exists' => 'Selecione uma categoria válida.',
            'name.required' => 'Informe o nome do produto.',
            'name.string' => 'Informe um nome válido.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'description.required' => 'Informe a descrição do produto.',
            'description.string' => 'Informe uma descrição válida.',
            'description.max' => 'A descrição deve ter no máximo 5000 caracteres.',
            'price.required' => 'Informe o preço.',
            'price.numeric' => 'Informe um preço numérico.',
            'price.decimal' => 'Use no máximo duas casas decimais no preço.',
            'price.min' => 'O preço não pode ser negativo.',
            'price.max' => 'O preço máximo é 99999999.99.',
            'stock_quantity.integer' => 'Informe uma quantidade inteira para o estoque.',
            'stock_quantity.min' => 'O estoque não pode ser negativo.',
            'stock_quantity.max' => 'O estoque máximo é 4294967295.',
            'availability_status.required' => 'Informe a disponibilidade do produto.',
            'availability_status.enum' => 'Selecione uma disponibilidade válida.',
            'active.required' => 'Informe o status do produto.',
            'active.boolean' => 'Informe um status válido para o produto.',
        ];
    }
}
