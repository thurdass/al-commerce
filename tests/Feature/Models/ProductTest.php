<?php

namespace Tests\Feature\Models;

use App\Models\Product;
use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_creates_product_through_store_relationship_with_defaults_and_optional_stock(): void
    {
        $store = Store::factory()->create();
        $attributes = Arr::except(Product::factory()->for($store)->raw([
            'name' => 'Mouse Logitech G305',
            'slug' => 'mouse-logitech-g305',
            'price' => '199.90',
        ]), ['store_id', 'active', 'availability_status', 'stock_quantity']);

        $product = $store->products()->create($attributes)->refresh();

        $this->assertModelExists($product);
        $this->assertSame($store->id, $product->store->id);
        $this->assertSame('Mouse Logitech G305', $product->name);
        $this->assertSame('mouse-logitech-g305', $product->slug);
        $this->assertSame('199.90', $product->price);
        $this->assertNull($product->stock_quantity);
        $this->assertSame(ProductAvailabilityStatus::Available, $product->availability_status);
        $this->assertTrue($product->active);
    }

    #[TestWith([0])]
    #[TestWith([12])]
    public function test_persists_stock_quantity_as_an_integer(int $quantity): void
    {
        $product = Product::factory()->create(['stock_quantity' => $quantity]);

        $this->assertSame($quantity, $product->fresh()->stock_quantity);
    }

    #[TestWith([ProductAvailabilityStatus::Available, 'AVAILABLE'])]
    #[TestWith([ProductAvailabilityStatus::LowStock, 'LOW_STOCK'])]
    #[TestWith([ProductAvailabilityStatus::OutOfStock, 'OUT_OF_STOCK'])]
    #[TestWith([ProductAvailabilityStatus::Inactive, 'INACTIVE'])]
    public function test_persists_availability_status_and_reads_it_as_an_enum(
        ProductAvailabilityStatus $status,
        string $storedValue,
    ): void {
        $product = Product::factory()->create(['availability_status' => $status]);

        $this->assertSame($status, $product->fresh()->availability_status);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'availability_status' => $storedValue,
        ]);
    }

    public function test_rejects_duplicate_product_slugs_in_the_same_store(): void
    {
        $product = Product::factory()->create(['slug' => 'mouse-logitech-g305']);

        $this->expectException(UniqueConstraintViolationException::class);

        Product::factory()->for($product->store)->create(['slug' => 'mouse-logitech-g305']);
    }

    public function test_allows_same_product_slug_in_different_stores(): void
    {
        $firstProduct = Product::factory()->create(['slug' => 'mouse-logitech-g305']);

        $secondProduct = Product::factory()->create(['slug' => 'mouse-logitech-g305']);

        $this->assertModelExists($firstProduct);
        $this->assertModelExists($secondProduct);
        $this->assertNotSame($firstProduct->store_id, $secondProduct->store_id);
    }

    public function test_rejects_product_without_an_existing_store(): void
    {
        $this->expectException(QueryException::class);

        Product::factory()->create(['store_id' => 999]);
    }

    public function test_database_rejects_unknown_availability_status(): void
    {
        $product = Product::factory()->create();

        $this->expectException(QueryException::class);

        Product::query()->whereKey($product->id)->update(['availability_status' => 'UNKNOWN']);
    }

    public function test_updates_product_price_stock_and_availability(): void
    {
        $product = Product::factory()->create();

        $product->update([
            'price' => '159.95',
            'stock_quantity' => 2,
            'availability_status' => ProductAvailabilityStatus::LowStock,
        ]);

        $product->refresh();

        $this->assertSame('159.95', $product->price);
        $this->assertSame(2, $product->stock_quantity);
        $this->assertSame(ProductAvailabilityStatus::LowStock, $product->availability_status);
    }

    public function test_product_can_be_deactivated(): void
    {
        $product = Product::factory()->create();

        $product->update([
            'active' => false,
            'availability_status' => ProductAvailabilityStatus::Inactive,
        ]);

        $product->refresh();

        $this->assertFalse($product->fresh()->active);
        $this->assertSame(ProductAvailabilityStatus::Inactive, $product->availability_status);
    }

    public function test_inactive_product_can_be_reactivated(): void
    {
        $product = Product::factory()->inactive()->create();

        $product->update([
            'active' => true,
            'availability_status' => ProductAvailabilityStatus::Available,
        ]);

        $product->refresh();

        $this->assertTrue($product->active);
        $this->assertSame(ProductAvailabilityStatus::Available, $product->availability_status);
    }

    public function test_mass_assignment_cannot_transfer_product_to_another_store(): void
    {
        $product = Product::factory()->create();
        $storeId = $product->store_id;
        $otherStore = Store::factory()->create();

        $product->update(['store_id' => $otherStore->id]);

        $this->assertSame($storeId, $product->fresh()->store_id);
    }

    public function test_deleting_product_preserves_store_and_other_products(): void
    {
        $product = Product::factory()->create();
        $store = $product->store;
        $otherProduct = Product::factory()->for($store)->create();

        $product->delete();

        $this->assertModelMissing($product);
        $this->assertModelExists($store);
        $this->assertModelExists($otherProduct);
    }
}
