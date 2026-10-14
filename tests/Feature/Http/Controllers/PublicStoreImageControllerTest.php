<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStoreImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_read_active_store_image_without_internal_paths(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'stores/private.png']);
        $content = file_get_contents(base_path('tests/Fixtures/product.png'));
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, $content);

        $response = $this->get(route('public.stores.image', ['store' => $store->slug]));

        $response->assertOk()->assertStreamedContent($content)->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringNotContainsString($store->logo, $response->headers->get('Content-Disposition'));
        Storage::disk(Store::IMAGE_DISK)->assertExists($store->logo);
    }

    public function test_deactivating_store_blocks_existing_public_image_url_but_owner_keeps_access(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'private.png']);
        Storage::disk(Store::IMAGE_DISK)->put($store->logo, 'bytes');
        $url = route('public.stores.image', ['store' => $store->slug]);
        $this->get($url)->assertOk();
        $store->update(['active' => false]);

        $this->get($url)->assertNotFound();
        $this->actingAs($store->user)->get($url)->assertNotFound();
        $this->get(route('stores.image.show', $store))->assertOk();
        Storage::disk(Store::IMAGE_DISK)->assertExists($store->logo);
    }

    public function test_missing_image_or_unknown_store_returns_404(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create();
        $url = route('public.stores.image', ['store' => $store->slug]);

        $this->get($url)->assertNotFound();
        $store->update(['logo' => 'missing.png']);
        $this->get($url)->assertNotFound();
        $this->get('/loja/unknown/imagem')->assertNotFound();
        Storage::disk(Store::IMAGE_DISK)->assertDirectoryEmpty('/');
    }

    public function test_own_store_image_takes_priority_and_removal_restores_product_fallback(): void
    {
        Storage::fake(Store::IMAGE_DISK);
        $store = Store::factory()->create(['logo' => 'own.png']);
        Storage::disk(Store::IMAGE_DISK)->put('own.png', 'bytes');
        $image = ProductImage::factory()->for(Product::factory()->for($store))->create();
        $storeImageUrl = route('public.stores.image', ['store' => $store->slug]);
        $productImageUrl = route('public.products.images.show', ['store' => $store->slug, 'product' => $image->product->slug, 'image' => $image->id]);

        $this->get(route('home'))->assertOk()->assertSee($storeImageUrl)->assertDontSee($productImageUrl)->assertDontSee($store->logo);
        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertOk()->assertSee($storeImageUrl)->assertDontSee($store->logo);

        $this->actingAs($store->user)->delete(route('stores.image.destroy', $store), ['confirmed' => '1'])
            ->assertRedirectToRoute('stores.edit', $store);

        $this->get(route('home'))->assertOk()->assertSee($productImageUrl)->assertDontSee($storeImageUrl);
        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertOk()->assertSee($productImageUrl)->assertDontSee($storeImageUrl);
        $this->get($storeImageUrl)->assertNotFound();
        Storage::disk(Store::IMAGE_DISK)->assertMissing('own.png');
    }

    public function test_each_store_uses_only_its_own_presentation_product(): void
    {
        $first = ProductImage::factory()->create();
        $second = ProductImage::factory()->create();
        $firstStore = $first->product->store;
        $secondStore = $second->product->store;

        $response = $this->get(route('home'))->assertOk();

        $stores = $response->viewData('stores')->keyBy('id');
        $this->assertSame($first->product_id, $stores[$firstStore->id]->presentationProduct->id);
        $this->assertSame($second->product_id, $stores[$secondStore->id]->presentationProduct->id);
    }
}
