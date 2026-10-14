<?php

namespace App\Actions;

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
