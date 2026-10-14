<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicProductImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_read_published_image_bytes_without_internal_paths(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();
        $content = file_get_contents(base_path('tests/Fixtures/product.png'));
        Storage::disk(ProductImage::DISK)->put($image->image_path, $content);

        $response = $this->get(route('public.products.images.show', [
            'store' => $image->product->store->slug, 'product' => $image->product->slug, 'image' => $image->id,
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertStreamedContent($content);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($image->image_path, $response->headers->get('Content-Disposition'));
        Storage::disk(ProductImage::DISK)->assertExists($image->image_path);
    }

    #[TestWith([false, true])]
    #[TestWith([true, false])]
    public function test_inactive_store_or_product_blocks_image_even_with_a_known_url(bool $storeActive, bool $productActive): void
    {
        Storage::fake(ProductImage::DISK);
        $store = Store::factory()->create(['active' => $storeActive]);
        $product = Product::factory()->for($store)->create(['active' => $productActive]);
        $image = ProductImage::factory()->for($product)->create();
        Storage::disk(ProductImage::DISK)->put($image->image_path, 'bytes');

        $this->get(route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $image->id]))
            ->assertNotFound();
        $this->actingAs($store->user)->get(route('products.images.show', [$product, $image]))->assertOk();
        Storage::disk(ProductImage::DISK)->assertExists($image->image_path);
    }

    public function test_image_cannot_be_read_under_another_product_or_store(): void
    {
        $image = ProductImage::factory()->create();
        $otherProduct = Product::factory()->for($image->product->store)->create();
        $otherStore = Store::factory()->create();

        $this->get(route('public.products.images.show', ['store' => $otherStore->slug, 'product' => $image->product->slug, 'image' => $image->id]))
            ->assertNotFound();
        $this->get(route('public.products.images.show', ['store' => $otherProduct->store->slug, 'product' => $otherProduct->slug, 'image' => $image->id]))
            ->assertNotFound();
    }

    public function test_missing_file_or_record_returns_404(): void
    {
        Storage::fake(ProductImage::DISK);
        $image = ProductImage::factory()->create();
        $parameters = ['store' => $image->product->store->slug, 'product' => $image->product->slug, 'image' => $image->id];

        $this->get(route('public.products.images.show', $parameters))->assertNotFound();
        $image->delete();
        $this->get(route('public.products.images.show', $parameters))->assertNotFound();
        Storage::disk(ProductImage::DISK)->assertMissing($image->image_path);
    }
}
