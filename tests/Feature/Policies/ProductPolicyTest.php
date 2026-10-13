<?php

namespace Tests\Feature\Policies;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_listing_requires_a_store_but_not_an_active_store(): void
    {
        $store = Store::factory()->inactive()->create();
        $withoutStore = User::factory()->create();

        $this->assertTrue(Gate::forUser($store->user)->allows('viewAny', Product::class));
        $this->assertFalse(Gate::forUser($withoutStore)->allows('viewAny', Product::class));
        $this->assertFalse(Gate::allows('viewAny', Product::class));
    }

    public function test_creation_requires_store_ownership_even_if_store_is_inactive(): void
    {
        $store = Store::factory()->inactive()->create();

        $this->assertTrue(Gate::forUser($store->user)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('create', Product::class));
        $this->assertFalse(Gate::allows('create', Product::class));
    }

    public function test_only_owner_can_update_active_or_inactive_products(): void
    {
        $store = Store::factory()->inactive()->create();
        $other = Store::factory()->create();
        $withoutStore = User::factory()->create();
        $active = Product::factory()->for($store)->create();
        $inactive = Product::factory()->for($store)->inactive()->create();

        foreach ([$active, $inactive] as $product) {
            $this->assertTrue(Gate::forUser($store->user)->allows('update', $product));
            $this->assertSame(404, Gate::forUser($other->user)->inspect('update', $product)->status());
            $this->assertSame(404, Gate::forUser($withoutStore)->inspect('update', $product)->status());
            $this->assertFalse(Gate::allows('update', $product));
        }
    }
}
