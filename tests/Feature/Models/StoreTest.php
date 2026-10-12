<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_store_through_user_relationship_with_optional_fields_and_defaults(): void
    {
        $user = User::factory()->create();
        $attributes = Arr::except(Store::factory()->for($user)->raw(), [
            'user_id', 'active', 'address', 'logo', 'cover_image', 'opening_hours',
        ]);

        $store = $user->store()->create($attributes)->refresh();

        $this->assertModelExists($store);
        $this->assertSame($user->id, $store->user->id);
        $this->assertSame($store->id, $user->fresh()->store->id);
        $this->assertTrue($store->active);
        $this->assertNull($store->address);
        $this->assertNull($store->logo);
        $this->assertNull($store->cover_image);
        $this->assertNull($store->opening_hours);
    }

    public function test_rejects_a_second_store_for_the_same_user(): void
    {
        $store = Store::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Store::factory()->for($store->user)->create();
    }

    public function test_rejects_duplicate_store_slugs_for_different_users(): void
    {
        Store::factory()->create(['slug' => 'tech-bahia']);

        $this->expectException(UniqueConstraintViolationException::class);

        Store::factory()->create(['slug' => 'tech-bahia']);
    }

    public function test_rejects_store_without_an_existing_user(): void
    {
        $this->expectException(QueryException::class);

        Store::factory()->create(['user_id' => 999]);
    }

    public function test_updates_store_details_and_deactivates_store(): void
    {
        $store = Store::factory()->create();

        $store->update([
            'name' => 'Tech Bahia',
            'address' => 'Rua da Bahia, 100',
            'logo' => 'stores/logo.png',
            'cover_image' => 'stores/cover.jpg',
            'opening_hours' => 'Segunda a sexta, das 9h às 18h',
            'active' => false,
        ]);

        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'name' => 'Tech Bahia',
            'address' => 'Rua da Bahia, 100',
            'logo' => 'stores/logo.png',
            'cover_image' => 'stores/cover.jpg',
            'opening_hours' => 'Segunda a sexta, das 9h às 18h',
            'active' => false,
        ]);
        $this->assertFalse($store->fresh()->active);
    }

    public function test_inactive_store_can_be_reactivated(): void
    {
        $store = Store::factory()->inactive()->create();

        $store->update(['active' => true]);

        $this->assertTrue($store->fresh()->active);
    }

    public function test_mass_assignment_cannot_transfer_store_to_another_user(): void
    {
        $store = Store::factory()->create();
        $ownerId = $store->user_id;
        $otherUser = User::factory()->create();

        $store->update(['user_id' => $otherUser->id]);

        $this->assertSame($ownerId, $store->fresh()->user_id);
    }

    public function test_returns_only_products_belonging_to_the_store(): void
    {
        $store = Store::factory()->create();
        $products = Product::factory()->count(2)->for($store)->create();
        Product::factory()->create();

        $this->assertSame(
            $products->modelKeys(),
            $store->products()->orderBy('id')->get()->modelKeys(),
        );
    }

    public function test_deleting_store_deletes_its_products_and_preserves_its_owner(): void
    {
        $store = Store::factory()->has(Product::factory())->create();
        $owner = $store->user;
        $product = $store->products->sole();

        $store->delete();

        $this->assertModelMissing($store);
        $this->assertModelMissing($product);
        $this->assertModelExists($owner);
    }
}
