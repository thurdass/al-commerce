<?php

namespace App\Actions;

use App\Models\Store;
use Illuminate\Support\Str;

class GenerateProductSlug
{
    public function handle(Store $store, string $name): string
    {
        $base = substr(Str::slug($name) ?: 'produto', 0, 240);
        $slug = $base;
        $suffix = 2;

        while ($store->products()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
