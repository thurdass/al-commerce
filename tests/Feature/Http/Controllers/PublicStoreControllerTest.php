<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PublicStoreControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_lists_active_stores_with_public_information_only(): void
    {
        $store = Store::factory()->create(['name' => 'Loja Local', 'address' => 'Endereco confidencial 123', 'description' => 'Produtos da nossa região.']);
        $inactive = Store::factory()->inactive()->create(['name' => 'Loja Oculta']);
        Product::factory()->count(2)->for($store)->create();
        Product::factory()->for($store)->inactive()->create();

        $this->get(route('home'))->assertOk()->assertSee('Loja Local')->assertSee('Produtos da nossa região.')
            ->assertSee('Salvador / BA')->assertSee('2 produtos ativos')->assertSee('Ver loja')
            ->assertSee(route('public.stores.show', ['store' => $store->slug]))
            ->assertDontSee($inactive->name)->assertDontSee($store->whatsapp)->assertDontSee($store->address)
            ->assertDontSee($store->user->email)->assertDontSee($store->user->name)
            ->assertDontSee(route('stores.edit', $store))->assertDontSee(route('products.index'));
    }

    public function test_home_paginates_stores_in_stable_name_and_id_order(): void
    {
        $stores = Store::factory()->count(13)->create(['name' => 'Loja']);
        Store::factory()->inactive()->create(['name' => 'Antes']);

        $response = $this->get(route('home'));

        $response->assertOk()->assertSee('Página 1 de 2 (13 lojas)')->assertSee('rel="next"', false);
        $this->assertSame($stores->take(12)->pluck('id')->all(), $response->viewData('stores')->pluck('id')->all());
        $response = $this->get(route('home', ['page' => 2]));
        $response->assertOk()->assertSee('rel="prev"', false)->assertDontSee('rel="next"', false);
        $this->assertSame([$stores->last()->id], $response->viewData('stores')->pluck('id')->all());
    }

    public function test_empty_home_and_store_have_clear_empty_states(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Nenhuma loja disponível no momento.');
        $store = Store::factory()->create();

        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertOk()
            ->assertSee('Esta loja ainda não tem produtos disponíveis para exibição.')->assertSee('Loja sem imagem');
    }

    public function test_store_only_lists_its_own_active_products_with_category_and_price(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create(['name' => 'Eletrônicos']);
        $product = Product::factory()->for($store)->for($category)->create(['name' => 'Produto visível', 'price' => '1234.50']);
        $inactive = Product::factory()->for($store)->inactive()->create(['name' => 'Produto oculto']);
        $other = Product::factory()->create(['name' => 'Produto de outra loja']);

        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertOk()
            ->assertSee($store->name)->assertSee($product->name)->assertSee('Eletrônicos')->assertSee('R$ 1.234,50')->assertSee('Disponível')
            ->assertSee(route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]))
            ->assertDontSee($inactive->name)->assertDontSee($other->name)->assertDontSee($store->whatsapp)
            ->assertSee(route('home'));
    }

    public function test_store_product_pagination_excludes_other_stores_and_inactive_products(): void
    {
        $store = Store::factory()->create();
        $products = Product::factory()->count(13)->for($store)->create();
        Product::factory()->for($store)->inactive()->create();
        Product::factory()->create();

        $response = $this->get(route('public.stores.show', ['store' => $store->slug]));

        $response->assertOk()->assertSee('Página 1 de 2 (13 produtos)');
        $this->assertSame($products->reverse()->take(12)->pluck('id')->all(), $response->viewData('products')->pluck('id')->all());
        $response = $this->get(route('public.stores.show', ['store' => $store->slug, 'page' => 2]));
        $response->assertOk()->assertSee($products->first()->name)->assertSee('rel="prev"', false);
        $this->assertSame([$products->first()->id], $response->viewData('products')->pluck('id')->all());
    }

    public function test_inactive_store_returns_404_even_to_its_owner_but_admin_edit_still_works(): void
    {
        $store = Store::factory()->inactive()->create();

        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertNotFound();
        $this->actingAs($store->user)->get(route('public.stores.show', ['store' => $store->slug]))->assertNotFound();
        $this->get(route('stores.edit', $store))->assertOk();
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Ver minha vitrine');
        $this->get('/loja/nonexistent-store')->assertNotFound();
    }

    public function test_home_uses_eligible_product_cover_and_never_inactive_or_unavailable_fallbacks(): void
    {
        $store = Store::factory()->create();
        $eligible = Product::factory()->for($store)->create(['availability_status' => ProductAvailabilityStatus::LowStock]);
        $cover = ProductImage::factory()->for($eligible)->create(['position' => 1]);
        ProductImage::factory()->for($eligible)->create(['position' => 2]);
        $inactive = Product::factory()->for($store)->inactive()->create();
        $unavailable = Product::factory()->for($store)->create(['availability_status' => ProductAvailabilityStatus::OutOfStock]);
        ProductImage::factory()->for($inactive)->create();
        ProductImage::factory()->for($unavailable)->create();

        $this->get(route('home'))->assertOk()
            ->assertSee(route('public.products.images.show', ['store' => $store->slug, 'product' => $eligible->slug, 'image' => $cover->id]))
            ->assertDontSee($inactive->slug)->assertDontSee($unavailable->slug)->assertDontSee($cover->image_path);

        $eligible->update(['active' => false]);
        $this->get(route('home'))->assertOk()->assertSee('Loja sem imagem')->assertDontSee($eligible->slug);
    }

    public function test_home_and_store_escape_public_fields_and_keep_description_short_on_home(): void
    {
        $name = '<script>store()</script>';
        $description = '<script>description()</script>'.str_repeat('a', 180).'hidden-ending';
        $store = Store::factory()->create(['name' => $name, 'description' => $description, 'city' => '<b>city</b>']);

        $this->get(route('home'))->assertOk()->assertSee($name)->assertDontSee($name, false)
            ->assertSee($store->city)->assertDontSee($store->city, false)->assertDontSee('hidden-ending');
        $this->get(route('public.stores.show', ['store' => $store->slug]))->assertOk()
            ->assertSee($description)->assertDontSee($description, false)->assertDontSee($name, false);
    }
}
