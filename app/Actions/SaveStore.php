<?php

namespace App\Actions;

use App\Models\Store;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveStore
{
    public function __construct(private GenerateStoreSlug $slugs) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Store $store, array $attributes): Store
    {
        $store->fill($attributes);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $store->slug = $this->slugs->handle($store->name);

            try {
                return DB::transaction(function () use ($store): Store {
                    $store->save();

                    return $store;
                });
            } catch (UniqueConstraintViolationException) {
                if (Store::query()->where('user_id', $store->user_id)->exists()) {
                    throw ValidationException::withMessages(['name' => 'Você já possui uma loja.']);
                }

            }
        }

        throw ValidationException::withMessages([
            'name' => 'Não foi possível reservar um slug. Tente novamente.',
        ]);
    }
}
