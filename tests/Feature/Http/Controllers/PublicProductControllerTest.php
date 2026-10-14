<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicProductControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_follow_home_store_and_product_links_with_gallery_in_cover_order(): void
    {
        $store = Store::factory()->create(['slug' => 'tech-bahia']);
        $category = Category::factory()->create();
        $product = Product::factory()->for($store)->for($category)->create(['slug' => 'mouse-g305', 'price' => '199.90']);
        $secondary = ProductImage::factory()->for($product)->create(['position' => 2]);
        $cover = ProductImage::factory()->for($product)->create(['position' => 1]);
        $storeUrl = route('public.stores.show', ['store' => $store->slug]);
        $productUrl = route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]);
        $coverUrl = route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $cover->id]);
        $secondaryUrl = route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $secondary->id]);

        $this->assertStringEndsWith('/loja/tech-bahia', $storeUrl);
        $this->assertStringEndsWith('/loja/tech-bahia/produto/mouse-g305', $productUrl);
        $this->get(route('home'))->assertOk()->assertSee($storeUrl);
        $this->get($storeUrl)->assertOk()->assertSee($productUrl);
        $this->get($productUrl)->assertOk()->assertSee($product->name)->assertSee($product->description)
            ->assertSee('R$ 199,90')->assertSee($category->name)->assertSee('Disponível')->assertSee($storeUrl)
            ->assertSeeInOrder([$coverUrl, $secondaryUrl])->assertDontSee($cover->image_path)
            ->assertDontSee(route('products.edit', $product))->assertDontSee($store->user->email)
            ->assertDontSeeText($store->whatsapp);
        $this->assertGuest();
    }

    #[TestWith([null])]
    #[TestWith([0])]
    #[TestWith([7])]
    public function test_stock_is_displayed_only_when_informed(?int $stock): void
    {
        $product = Product::factory()->create(['stock_quantity' => $stock]);

        $response = $this->get(route('public.products.show', ['store' => $product->store->slug, 'product' => $product->slug]));

        $response->assertOk()->assertSee('Sem categoria')->assertSee('Produto sem imagem');
        if ($stock === null) {
            $response->assertDontSee('Quantidade em estoque');
        } else {
            $response->assertSee('Quantidade em estoque')->assertSee('<dd>'.$stock.'</dd>', false);
        }
    }

    #[TestWith([ProductAvailabilityStatus::Available, 'Disponível'])]
    #[TestWith([ProductAvailabilityStatus::LowStock, 'Estoque baixo'])]
    #[TestWith([ProductAvailabilityStatus::OutOfStock, 'Sem estoque'])]
    #[TestWith([ProductAvailabilityStatus::Inactive, 'Indisponível'])]
    public function test_availability_does_not_override_the_existing_active_flag(ProductAvailabilityStatus $status, string $label): void
    {
        $product = Product::factory()->create(['active' => true, 'availability_status' => $status]);

        $this->get(route('public.products.show', ['store' => $product->store->slug, 'product' => $product->slug]))
            ->assertOk()->assertSee($label)->assertSee('Falar com a loja pelo WhatsApp');
    }

    #[TestWith([false, true])]
    #[TestWith([true, false])]
    #[TestWith([false, false])]
    public function test_inactive_store_or_product_returns_404_including_for_owner(bool $storeActive, bool $productActive): void
    {
        $store = Store::factory()->create(['active' => $storeActive]);
        $product = Product::factory()->for($store)->create(['active' => $productActive]);
        $url = route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]);

        $this->get($url)->assertNotFound()->assertDontSee('https://wa.me/');
        $this->actingAs($store->user)->get($url)->assertNotFound()->assertDontSee('https://wa.me/');
        $this->get(route('products.edit', $product))->assertOk();
    }

    public function test_slug_is_scoped_to_store_and_does_not_fall_back_to_another_store(): void
    {
        $first = Product::factory()->create(['slug' => 'same-slug', 'name' => 'Primeiro produto']);
        $second = Product::factory()->create(['slug' => 'same-slug', 'name' => 'Segundo produto']);
        $foreign = Product::factory()->for($second->store)->create(['slug' => 'foreign-slug']);

        $this->get(route('public.products.show', ['store' => $first->store->slug, 'product' => 'same-slug']))
            ->assertOk()->assertSee($first->name)->assertDontSee($second->name);
        $this->get(route('public.products.show', ['store' => $second->store->slug, 'product' => 'same-slug']))
            ->assertOk()->assertSee($second->name)->assertDontSee($first->name);
        $this->get(route('public.products.show', ['store' => $first->store->slug, 'product' => $foreign->slug]))->assertNotFound();
        $this->get(route('public.products.show', ['store' => $first->store->slug, 'product' => 'missing']))->assertNotFound();
    }

    public function test_product_description_name_and_category_are_escaped(): void
    {
        $category = Category::factory()->create(['name' => '<script>category()</script>']);
        $product = Product::factory()->for($category)->create(['name' => '<script>product()</script>', 'description' => '<script>description()</script>']);

        $this->get(route('public.products.show', ['store' => $product->store->slug, 'product' => $product->slug]))
            ->assertOk()->assertSee($product->name)->assertDontSee($product->name, false)
            ->assertSee($product->description)->assertDontSee($product->description, false)
            ->assertSee($category->name)->assertDontSee($category->name, false);
    }
}
