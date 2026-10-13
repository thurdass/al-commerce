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
}
