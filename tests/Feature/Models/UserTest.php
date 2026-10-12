<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_exist_without_a_store(): void
    {
        $user = User::factory()->create();

        $this->assertModelExists($user);
        $this->assertNull($user->store);
    }

    public function test_deleting_user_deletes_only_their_store_and_products(): void
    {
        $store = Store::factory()->has(Product::factory()->count(2))->create();
        $user = $store->user;
        $products = $store->products;
        $otherProduct = Product::factory()->create();

        $user->delete();

        $this->assertModelMissing($user);
        $this->assertModelMissing($store);

        foreach ($products as $product) {
            $this->assertModelMissing($product);
        }

        $this->assertModelExists($otherProduct);
        $this->assertModelExists($otherProduct->store);
        $this->assertModelExists($otherProduct->store->user);
    }
}
