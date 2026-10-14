<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicStoreProductSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_store_filters_are_combined_without_crossing_the_store_boundary(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create();
        $attributes = ['name' => 'Mouse Logitech', 'availability_status' => 'LOW_STOCK'];
        $match = Product::factory()->for($store)->for($category)->create($attributes);
        $other = Product::factory()->for($category)->create($attributes);
        Product::factory()->for($store)->for($category)->create($attributes + ['active' => false]);
        Product::factory()->for($store)->for($category)->create(['name' => 'Mouse disponível']);
        Product::factory()->for($store)->for($category)->create(['name' => 'Teclado', 'availability_status' => 'LOW_STOCK']);
        Product::factory()->for($store)->create($attributes);

        $response = $this->get(route('public.stores.show', [
            'store' => $store->slug, 'q' => 'logi', 'categoria' => $category->slug,
            'disponibilidade' => 'LOW_STOCK', 'store_id' => $other->store_id,
            'active' => '0', 'tipo' => 'lojas', 'cidade' => 'outra cidade',
        ]));

        $response->assertOk()->assertSee($match->name)->assertDontSee('name="cidade"', false)
            ->assertDontSee('name="tipo"', false);
        $this->assertSame([$match->id], $response->viewData('products')->pluck('id')->all());
        $this->assertSame(['q' => 'logi', 'categoria' => $category->slug, 'disponibilidade' => 'LOW_STOCK'], $response->viewData('filters'));
    }

    public function test_store_pagination_keeps_filters_and_stays_inside_its_slug_url(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create();
        $products = Product::factory()->count(13)->for($store)->for($category)->create(['name' => 'Mouse', 'price' => '10.00']);
        Product::factory()->for($category)->create(['name' => 'Mouse']);
        $filters = ['q' => 'Mouse', 'categoria' => $category->slug, 'disponibilidade' => 'AVAILABLE', 'ordenar' => 'maior_preco'];
        $url = route('public.stores.show', ['store' => $store->slug]);

        $response = $this->get(route('public.stores.show', ['store' => $store->slug] + $filters));
        $response->assertOk()->assertSee('Página 1 de 2 (13 produtos)')->assertSee('href="'.$url.'">Limpar filtros', false);
        $paginator = $response->viewData('products');
        $this->assertSame($products->take(12)->pluck('id')->all(), $paginator->pluck('id')->all());
        $this->assertSame(parse_url($url, PHP_URL_PATH), parse_url($paginator->nextPageUrl(), PHP_URL_PATH));
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => '2'], $query);
        $this->get($paginator->nextPageUrl())->assertOk()->assertViewHas('products',
            fn ($page) => $page->pluck('id')->all() === [$products->last()->id]);
    }

    public function test_inactive_store_returns_404_even_with_invalid_filters_and_an_authenticated_owner(): void
    {
        $store = Store::factory()->inactive()->create();
        $url = route('public.stores.show', ['store' => $store->slug, 'q' => ['invalid'], 'ordenar' => 'invalid']);

        $this->get($url)->assertNotFound();
        $this->actingAs($store->user)->get($url)->assertNotFound();
    }

    public function test_invalid_filters_return_to_the_same_store_without_a_redirect_loop(): void
    {
        $store = Store::factory()->create();
        $url = route('public.stores.show', ['store' => $store->slug]);

        $this->get(route('public.stores.show', ['store' => $store->slug, 'categoria' => 'nonexistent']))
            ->assertRedirect($url)->assertSessionHasErrors(['categoria' => 'Escolha uma categoria válida.']);
        $this->get($url)->assertOk();
    }

    public function test_filtered_store_has_a_clear_empty_state(): void
    {
        $store = Store::factory()->create();
        Product::factory()->for($store)->create(['name' => 'Mouse']);

        $this->get(route('public.stores.show', ['store' => $store->slug, 'q' => 'Teclado']))->assertOk()
            ->assertSee('Nenhum produto desta loja encontrado com os filtros informados.')
            ->assertDontSee('Esta loja ainda não tem produtos disponíveis para exibição.');
    }

    public function test_store_search_does_not_add_queries_for_each_product(): void
    {
        $store = Store::factory()->create();
        Product::factory()->count(2)->for($store)->for(Category::factory())->has(ProductImage::factory(), 'images')
            ->create(['name' => 'Mouse']);
        $connection = DB::connection();
        $connection->enableQueryLog();

        try {
            $this->get(route('public.stores.show', ['store' => $store->slug, 'q' => 'Mouse']))->assertOk();
            $initialCount = count($connection->getQueryLog());
            Product::factory()->count(5)->for($store)->for(Category::factory())->has(ProductImage::factory(), 'images')
                ->create(['name' => 'Mouse']);
            $connection->flushQueryLog();
            $this->get(route('public.stores.show', ['store' => $store->slug, 'q' => 'Mouse']))->assertOk()
                ->assertViewHas('products', fn ($products) => $products->count() === 7);
            $this->assertSame($initialCount, count($connection->getQueryLog()));
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
    }
}
