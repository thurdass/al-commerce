<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_list_products(): void
    {
        $this->get(route('products.index'))->assertRedirectToRoute('login');
    }

    public function test_user_without_store_cannot_list_products(): void
    {
        $this->actingAs(User::factory()->create())->get(route('products.index'))->assertForbidden();
    }

    public function test_lists_only_own_products_including_inactive_with_stable_pagination(): void
    {
        $store = Store::factory()->inactive()->create();
        $oldest = Product::factory()->for($store)->inactive()->create(['name' => 'Produto mais antigo']);
        $products = Product::factory()->count(15)->for($store)->create();
        $other = Product::factory()->create(['name' => 'Produto de outra loja']);

        $response = $this->actingAs($store->user)->get(route('products.index', ['store_id' => $other->store_id]));

        $response->assertOk()->assertViewIs('products.index')->assertDontSee($other->name)->assertDontSee($oldest->name)
            ->assertSee('Página 1 de 2')->assertSee('rel="next"', false);
        $this->assertSame($products->reverse()->pluck('id')->all(), $response->viewData('products')->pluck('id')->all());
        $this->assertSame(16, $response->viewData('products')->total());

        $this->get(route('products.index', ['page' => 2]))->assertOk()->assertSee($oldest->name)
            ->assertSee('Inativo')->assertSee('Indisponível')->assertSee('rel="prev"', false)->assertDontSee('rel="next"', false);
    }

    public function test_empty_list_is_accessible_from_dashboard(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->get(route('dashboard'))->assertOk()->assertSee(route('products.index'));
        $this->get(route('products.index'))->assertOk()->assertSee('Nenhum produto cadastrado.');
    }

    public function test_dashboard_without_store_does_not_link_to_product_management(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
            ->assertDontSee(route('products.index'))->assertSee(route('stores.create'));
    }

    public function test_list_escapes_names_and_formats_price_and_zero_stock(): void
    {
        $store = Store::factory()->create(['name' => '<script>store()</script>']);
        $product = Product::factory()->for($store)->create([
            'name' => '<script>product()</script>', 'price' => '1234.50', 'stock_quantity' => 0,
        ]);

        $this->actingAs($store->user)->get(route('products.index'))->assertOk()
            ->assertSee($store->name)->assertDontSee($store->name, false)
            ->assertSee($product->name)->assertDontSee($product->name, false)
            ->assertSee('R$ 1.234,50')->assertSee('Disponível')->assertSee('Ativo')
            ->assertSee('<dd>0</dd>', false)->assertDontSee('Não informado');
    }
}
