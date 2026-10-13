<?php

namespace App\Actions;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GenerateStoreSlug
{
    public function handle(string $name, ?int $exceptStoreId = null): string
    {
        $base = substr(Str::slug($name) ?: 'loja', 0, 240);
        $slug = $base;
        $suffix = 2;

        while (Store::query()->where('slug', $slug)
            ->when($exceptStoreId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptStoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
