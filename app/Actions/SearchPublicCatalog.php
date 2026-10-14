<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchPublicCatalog
{
    /**
     * @param  array{q?: string, cidade?: string, estado?: string, ordenar?: string}  $filters
     * @return LengthAwarePaginator<int, Store>
     */
    public function stores(array $filters): LengthAwarePaginator
    {
        $query = Store::query()->active()
            ->withCount(['products as active_products_count' => fn (Builder $query) => $query->active()])
            ->with('presentationProduct.coverImage');

        $this->containing($query, 'name', $filters['q'] ?? null);
        $this->location($query, $filters);

        if (($filters['ordenar'] ?? 'nome') === 'recentes') {
            $query->orderByDesc('id');
        } else {
            $query->orderBy('name')->orderBy('id');
        }

        return $query->paginate(12)->appends($filters);
    }

    /**
     * @param  array{q?: string, cidade?: string, estado?: string, categoria?: string, disponibilidade?: string, ordenar?: string, tipo?: string}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function products(array $filters, ?Store $store = null): LengthAwarePaginator
    {
        $query = Product::query()->active()
            ->whereHas('store', function (Builder $query) use ($filters): void {
                $query->active();
                $this->location($query, $filters);
            })
            ->with(['category', 'coverImage']);

        if ($store !== null) {
            $query->where('store_id', $store->id);
        } else {
            $query->with('store:id,name,slug,city,state');
        }

        $this->containing($query, 'name', $filters['q'] ?? null);

        if (isset($filters['categoria'])) {
            $query->whereHas('category', fn (Builder $query) => $query->where('slug', $filters['categoria']));
        }

        if (isset($filters['disponibilidade'])) {
            $query->where('availability_status', $filters['disponibilidade']);
        }

        match ($filters['ordenar'] ?? 'recentes') {
            'nome' => $query->orderBy('name')->orderBy('id'),
            'menor_preco' => $query->orderBy('price')->orderBy('id'),
            'maior_preco' => $query->orderByDesc('price')->orderBy('id'),
            default => $query->orderByDesc('id'),
        };

        return $query->paginate(12)->appends($filters);
    }

    /** @param array{cidade?: string, estado?: string} $filters */
    private function location(Builder $query, array $filters): void
    {
        $this->containing($query, 'city', $filters['cidade'] ?? null);

        if (isset($filters['estado'])) {
            $query->where('state', $filters['estado']);
        }
    }

    private function containing(Builder $query, string $column, ?string $value): void
    {
        if ($value !== null) {
            $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
            $query->whereRaw($column." LIKE ? ESCAPE '!'", [$pattern]);
        }
    }
}
