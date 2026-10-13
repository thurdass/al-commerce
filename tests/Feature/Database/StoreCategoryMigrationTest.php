<?php

namespace Tests\Feature\Database;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class StoreCategoryMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_category_migration_and_rollback_preserve_stores_and_products(): void
    {
        $store = Store::factory()->has(Product::factory())->create(['category' => null]);
        $product = $store->products->sole();
        $categorizedStore = Store::factory()->create(['category' => 'Eletrônicos']);
        $migration = require database_path('migrations/2026_10_13_011353_make_store_category_nullable.php');

        $migration->down();

        $this->assertSame('', $store->fresh()->category);
        $this->assertSame('Eletrônicos', $categorizedStore->fresh()->category);
        $this->assertModelExists($product);

        $migration->up();
        $store->refresh()->update(['category' => null]);

        $this->assertNull($store->fresh()->category);
        $this->assertSame('Eletrônicos', $categorizedStore->fresh()->category);
        $this->assertModelExists($product);
        $this->assertSame($store->id, $product->fresh()->store_id);
    }
}
