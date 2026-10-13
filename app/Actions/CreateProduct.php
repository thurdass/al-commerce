<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProduct
{
    public function __construct(private GenerateProductSlug $slugs) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Store $store, array $attributes): Product
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $product = $store->products()->make($attributes);
            $product->slug = $this->slugs->handle($store, $product->name);

            try {
                return DB::transaction(function () use ($product): Product {
                    $product->save();

                    return $product;
                });
            } catch (UniqueConstraintViolationException) {
                // A concurrent insert may have reserved the slug after the lookup.
            }
        }

        throw ValidationException::withMessages([
            'name' => 'Não foi possível reservar um slug. Tente novamente.',
        ]);
    }
}
