<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_catalog_can_be_seeded_again_without_duplicates_or_changed_associations(): void
    {
        $this->seed(CategorySeeder::class);
        $category = Category::query()->where('slug', 'eletronicos-e-informatica')->firstOrFail();
        $product = Product::factory()->for($category)->create();

        $this->seed(CategorySeeder::class);

        $this->assertDatabaseCount('categories', 8);
        $this->assertSame($category->id, $product->fresh()->category->id);
        $this->assertSame($product->id, $category->products()->sole()->id);
    }

    #[TestWith(['name'])]
    #[TestWith(['slug'])]
    public function test_database_rejects_duplicate_categories(string $field): void
    {
        $category = Category::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Category::factory()->create([$field => $category->$field]);
    }

    public function test_removing_category_preserves_its_products(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        $category->delete();

        $this->assertModelExists($product);
        $this->assertNull($product->fresh()->category_id);
    }
}
