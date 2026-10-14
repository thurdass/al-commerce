<?php

namespace App\Http\Requests;

use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $store = $this->route('store');

        if ($store instanceof Store) {
            abort_unless($store->active, 404);
        }

        return true;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'tipo' => [Rule::excludeIf($this->withinStore()), 'nullable', 'string', Rule::in(['lojas', 'produtos'])],
            'q' => ['nullable', 'string', 'max:255'],
            'cidade' => [Rule::excludeIf($this->withinStore()), 'nullable', 'string', 'max:255'],
            'estado' => [Rule::excludeIf($this->withinStore()), 'nullable', 'string', Rule::in([
                'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
                'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
                'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
            ])],
            'categoria' => [Rule::excludeIf(! $this->searchesProducts()), 'nullable', 'string', 'max:255', Rule::exists('categories', 'slug')],
            'disponibilidade' => [Rule::excludeIf(! $this->searchesProducts()), 'nullable', Rule::enum(ProductAvailabilityStatus::class)],
            'ordenar' => ['nullable', 'string', Rule::in($this->searchesProducts()
                ? ['nome', 'recentes', 'menor_preco', 'maior_preco']
                : ['nome', 'recentes'])],
        ];
    }

    public function searchesProducts(): bool
    {
        return $this->withinStore() || $this->input('tipo') === 'produtos';
    }

    public function withinStore(): bool
    {
        return $this->routeIs('public.stores.show');
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
        return $this->searchesProducts() && ! $this->withinStore()
            ? route('home', ['tipo' => 'produtos'])
            : $this->url();
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'tipo.string' => 'Escolha buscar lojas ou produtos.',
            'tipo.in' => 'Escolha buscar lojas ou produtos.',
            'q.string' => 'Informe um termo de busca válido.',
            'q.max' => 'A busca deve ter no máximo 255 caracteres.',
            'cidade.string' => 'Informe uma cidade válida.',
            'cidade.max' => 'A cidade deve ter no máximo 255 caracteres.',
            'estado.string' => 'Informe uma UF válida.',
            'estado.in' => 'Informe uma UF válida.',
            'ordenar.string' => 'Escolha uma ordenação válida.',
            'ordenar.in' => 'Escolha uma ordenação válida.',
            'categoria.string' => 'Escolha uma categoria válida.',
            'categoria.max' => 'Escolha uma categoria válida.',
            'categoria.exists' => 'Escolha uma categoria válida.',
            'disponibilidade.enum' => 'Escolha uma disponibilidade válida.',
        ];
    }
}
