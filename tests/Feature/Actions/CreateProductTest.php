<?php

namespace Tests\Feature\Actions;

use App\Actions\CreateProduct;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_retries_slug_collision_during_insert(): void
    {
        $store = Store::factory()->create();
        Product::factory()->for($store)->create(['slug' => 'taken']);
        $collide = true;
        Product::creating(function (Product $product) use (&$collide): void {
            if ($collide) {
                $product->slug = 'taken';
                $collide = false;
            }
        });

        $product = app(CreateProduct::class)->handle($store, Product::factory()->for($store)->raw(['name' => 'Mouse']));

        $this->assertSame('mouse', $product->fresh()->slug);
        $this->assertSame($store->id, $product->store_id);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_persistent_collisions_do_not_persist_partial_product(): void
    {
        $store = Store::factory()->create();
        $existing = Product::factory()->for($store)->create(['slug' => 'taken']);
        Product::creating(function (Product $product): void {
            $product->slug = 'taken';
        });

        try {
            app(CreateProduct::class)->handle($store, Product::factory()->for($store)->raw());
            $this->fail('A conflicting product must not be persisted.');
        } catch (ValidationException $exception) {
            $this->assertSame(['name' => ['Não foi possível reservar um slug. Tente novamente.']], $exception->errors());
            $this->assertModelExists($existing);
            $this->assertDatabaseCount('products', 1);
        }
    }
}
