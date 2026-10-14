<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\ProductAvailabilityStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PublicProductSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unfiltered_discovery_never_lists_inactive_products_or_inactive_stores(): void
    {
        $visible = Product::factory()->create();
        Product::factory()->inactive()->create();
        Product::factory()->for(Store::factory()->inactive())->create();

        $this->get(route('home', ['tipo' => 'produtos']))->assertOk()->assertDontSee('Lojas em destaque')->assertViewHas('products',
            fn ($products) => $products->pluck('id')->all() === [$visible->id]);
    }

    public function test_product_discovery_combines_partial_search_category_location_and_availability(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create();
        $attributes = ['name' => 'Mouse Logitech', 'availability_status' => ProductAvailabilityStatus::LowStock];
        $match = Product::factory()->for($store)->for($category)->create($attributes);
        Product::factory()->for($store)->for($category)->create($attributes + ['active' => false]);
        Product::factory()->for($store)->for($category)->create(['name' => 'Teclado', 'availability_status' => ProductAvailabilityStatus::LowStock]);
        Product::factory()->for($store)->for($category)->create(['name' => 'Mouse Disponível']);
        Product::factory()->for($store)->create($attributes);
        Product::factory()->for($category)->for(Store::factory()->inactive())->create($attributes);
        Product::factory()->for($category)->for(Store::factory()->state(['city' => 'Feira de Santana']))->create($attributes);
        Product::factory()->for($category)->for(Store::factory()->state(['state' => 'SP']))->create($attributes);
        $filters = ['tipo' => 'produtos', 'q' => 'logi', 'categoria' => $category->slug,
            'cidade' => 'salv', 'estado' => 'ba', 'disponibilidade' => 'LOW_STOCK'];

        $response = $this->get(route('home', $filters));

        $response->assertOk()->assertSee($match->name)->assertSee($category->name)
            ->assertSee('value="LOW_STOCK" selected', false)
            ->assertSee('value="'.$category->slug.'" selected', false);
        $this->assertSame([$match->id], $response->viewData('products')->pluck('id')->all());
    }

    public function test_discovery_links_to_public_product_and_store_without_exposing_private_fields(): void
    {
        $store = Store::factory()->create(['address' => 'Endereço privado 999']);
        $product = Product::factory()->for($store)->create(['name' => '<script>produto()</script>', 'price' => '25.90']);
        $image = ProductImage::factory()->for($product)->create();

        $this->get(route('home', ['tipo' => 'produtos']))->assertOk()
            ->assertSee('R$ 25,90')->assertSee('Sem categoria')->assertSee($store->name)
            ->assertSee($product->name)->assertDontSee($product->name, false)
            ->assertSee(route('public.products.show', ['store' => $store->slug, 'product' => $product->slug]))
            ->assertSee(route('public.stores.show', ['store' => $store->slug]))
            ->assertSee(route('public.products.images.show', ['store' => $store->slug, 'product' => $product->slug, 'image' => $image->id]))
            ->assertDontSee($image->image_path)->assertDontSee($store->address)->assertDontSee($store->whatsapp)
            ->assertDontSee($store->user->email)->assertDontSee(route('products.edit', $product));
    }

    #[TestWith(['AVAILABLE'])]
    #[TestWith(['LOW_STOCK'])]
    #[TestWith(['OUT_OF_STOCK'])]
    #[TestWith(['INACTIVE'])]
    public function test_availability_does_not_override_product_or_store_visibility(string $status): void
    {
        $store = Store::factory()->create();
        $expected = null;
        foreach (ProductAvailabilityStatus::cases() as $availability) {
            $product = Product::factory()->for($store)->create(['availability_status' => $availability]);
            if ($availability->value === $status) {
                $expected = $product;
            }
        }
        Product::factory()->for($store)->create(['active' => false, 'availability_status' => $status]);
        Product::factory()->for(Store::factory()->inactive())->create(['availability_status' => $status]);

        $response = $this->get(route('home', ['tipo' => 'produtos', 'disponibilidade' => $status]));
        $response->assertOk();
        $this->assertSame([$expected->id], $response->viewData('products')->pluck('id')->all());
    }

    #[TestWith(['nome', [1, 2, 0]])]
    #[TestWith(['recentes', [2, 1, 0]])]
    #[TestWith(['menor_preco', [1, 2, 0]])]
    #[TestWith(['maior_preco', [0, 1, 2]])]
    public function test_sort_options_have_a_stable_id_tiebreaker(string $sort, array $positions): void
    {
        $products = Product::factory()->count(3)->sequence(
            ['name' => 'Z Produto', 'price' => '100.00'],
            ['name' => 'A Produto', 'price' => '9.90'],
            ['name' => 'A Produto', 'price' => '9.90'],
        )->create();

        $response = $this->get(route('home', ['tipo' => 'produtos', 'ordenar' => $sort]));
        $response->assertOk();
        $this->assertSame(array_map(fn (int $position) => $products[$position]->id, $positions), $response->viewData('products')->pluck('id')->all());
    }

    public function test_product_pagination_preserves_all_filters(): void
    {
        $category = Category::factory()->create();
        $store = Store::factory()->create();
        $products = Product::factory()->count(13)->for($store)->for($category)->create(['name' => 'Mouse', 'price' => '10.00']);
        $filters = ['tipo' => 'produtos', 'q' => 'Mouse', 'cidade' => 'Salvador', 'estado' => 'BA',
            'categoria' => $category->slug, 'disponibilidade' => 'AVAILABLE', 'ordenar' => 'menor_preco'];

        $response = $this->get(route('home', $filters + ['active' => '0', 'store_id' => '999']));
        $response->assertOk()->assertSee('Página 1 de 2 (13 produtos)');
        $paginator = $response->viewData('products');
        $this->assertSame($products->take(12)->pluck('id')->all(), $paginator->pluck('id')->all());
        parse_str(parse_url($paginator->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertEquals($filters + ['page' => '2'], $query);
        $response->assertSee($paginator->nextPageUrl());
        $this->get($paginator->nextPageUrl())->assertOk()->assertViewHas('products',
            fn ($page) => $page->pluck('id')->all() === [$products->last()->id]);
    }

    public function test_product_search_escapes_like_wildcards(): void
    {
        $match = Product::factory()->create(['name' => 'Mouse 100%_! especial']);
        Product::factory()->create(['name' => 'Mouse comum']);
        $response = $this->get(route('home', ['tipo' => 'produtos', 'q' => '%_!']));
        $response->assertOk();
        $this->assertSame([$match->id], $response->viewData('products')->pluck('id')->all());
    }

    public function test_product_search_has_an_empty_state_and_clear_link_preserves_mode(): void
    {
        $this->get(route('home', ['tipo' => 'produtos', 'q' => 'ausente']))->assertOk()
            ->assertSee('Nenhum produto encontrado com os filtros informados.')
            ->assertSee('href="'.route('home', ['tipo' => 'produtos']).'">Limpar filtros', false);
    }

    #[TestWith(['categoria', 'nonexistent', 'Escolha uma categoria válida.'])]
    #[TestWith(['categoria', ['invalid'], 'Escolha uma categoria válida.'])]
    #[TestWith(['disponibilidade', 'INVALID', 'Escolha uma disponibilidade válida.'])]
    #[TestWith(['disponibilidade', ['AVAILABLE'], 'Escolha uma disponibilidade válida.'])]
    #[TestWith(['ordenar', 'price desc; --', 'Escolha uma ordenação válida.'])]
    public function test_invalid_product_filters_return_to_product_discovery(string $field, mixed $value, string $message): void
    {
        $this->get(route('home', ['tipo' => 'produtos', $field => $value]))
            ->assertRedirect(route('home', ['tipo' => 'produtos']))->assertSessionHasErrors([$field => $message]);
    }

    public function test_unknown_search_mode_is_rejected(): void
    {
        $this->get(route('home', ['tipo' => 'users']))->assertRedirect(route('home'))
            ->assertSessionHasErrors(['tipo' => 'Escolha buscar lojas ou produtos.']);
    }

    public function test_query_count_does_not_grow_with_result_count(): void
    {
        ProductImage::factory()->for(Product::factory()->for(Category::factory()))->create();
        $connection = DB::connection();
        $connection->enableQueryLog();

        try {
            $this->get(route('home', ['tipo' => 'produtos']))->assertOk()->assertViewHas('products',
                fn ($products) => $products->count() === 1);
            $initialCount = count($connection->getQueryLog());
            Product::factory()->count(5)->for(Category::factory())->has(ProductImage::factory(), 'images')->create();
            $connection->flushQueryLog();
            $this->get(route('home', ['tipo' => 'produtos']))->assertOk()->assertViewHas('products',
                fn ($products) => $products->count() === 6);
            $this->assertSame($initialCount, count($connection->getQueryLog()));
        } finally {
            $connection->disableQueryLog();
            $connection->flushQueryLog();
        }
    }
}
