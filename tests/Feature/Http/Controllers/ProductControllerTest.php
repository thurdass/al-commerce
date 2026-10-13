<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\ProductAvailabilityStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_owner_can_view_creation_form_and_create_in_own_store(): void
    {
        $store = Store::factory()->inactive()->create();
        $other = Store::factory()->create();
        $this->actingAs($store->user)->get(route('products.create'))->assertOk()
            ->assertViewIs('products.create')->assertSee('name="_token"', false);

        $this->post(route('products.store'), $this->payload([
            'store_id' => $other->id, 'user_id' => $other->user_id, 'slug' => 'injected',
            'category_name' => 'Injected', 'id' => 999,
        ]))->assertRedirectToRoute('products.index')->assertSessionHasNoErrors();

        $product = Product::query()->sole();
        $this->assertSame($store->id, $product->store_id);
        $this->assertSame('mouse-logitech-g305', $product->slug);
        $this->assertSame('Mouse Logitech G305', $product->name);
        $this->assertSame('Mouse sem fio.', $product->description);
        $this->assertSame('199.90', $product->price);
        $this->assertNull($product->stock_quantity);
        $this->assertSame(ProductAvailabilityStatus::Available, $product->availability_status);
        $this->assertTrue($product->active);
        $this->assertNotSame(999, $product->id);
        $this->assertSame(0, $other->products()->count());
    }

    public function test_creation_ignores_slugs_in_other_stores_but_reserves_inactive_slugs_in_own_store(): void
    {
        $store = Store::factory()->create();
        Product::factory()->create(['slug' => 'mouse-logitech-g305']);
        $this->actingAs($store->user)->post(route('products.store'), $this->payload())
            ->assertRedirectToRoute('products.index');
        $this->assertSame('mouse-logitech-g305', $store->products()->sole()->slug);
        Product::factory()->for($store)->inactive()->create(['slug' => 'mouse-logitech-g305-2']);

        $this->post(route('products.store'), $this->payload())->assertRedirectToRoute('products.index');

        $this->assertSame('mouse-logitech-g305-3', $store->products()->orderByDesc('id')->first()->slug);
    }

    #[DataProvider('productNames')]
    public function test_generates_safe_slugs(string $name, string $slug): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->post(route('products.store'), $this->payload(['name' => $name]))
            ->assertRedirectToRoute('products.index');

        $this->assertSame($slug, $store->products()->sole()->slug);
    }

    public static function productNames(): array
    {
        return [
            'accents' => ['Café Especial', 'cafe-especial'],
            'fallback' => ['!!!', 'produto'],
            'long name' => [str_repeat('a', 255), str_repeat('a', 240)],
        ];
    }

    public function test_guest_cannot_create_products(): void
    {
        $this->get(route('products.create'))->assertRedirectToRoute('login');
        $this->post(route('products.store'), $this->payload())->assertRedirectToRoute('login');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_user_without_store_cannot_create_products_even_with_forged_store_id(): void
    {
        $store = Store::factory()->create();
        $this->actingAs(User::factory()->create())->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), $this->payload(['store_id' => $store->id]))->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_missing_required_fields_do_not_create_product(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->post(route('products.store'), [])->assertSessionHasErrors([
            'name' => 'Informe o nome do produto.', 'description' => 'Informe a descrição do produto.',
            'price' => 'Informe o preço.', 'availability_status' => 'Informe a disponibilidade do produto.',
            'active' => 'Informe o status do produto.',
        ]);

        $this->assertDatabaseCount('products', 0);
    }

    #[DataProvider('invalidProductData')]
    public function test_invalid_data_does_not_create_product(array $changes, string $field, string $message): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->from(route('products.create'))
            ->post(route('products.store'), $this->payload($changes))
            ->assertRedirectToRoute('products.create')->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('products', 0);
    }

    public static function invalidProductData(): array
    {
        return [
            'name type' => [['name' => ['bad']], 'name', 'Informe um nome válido.'],
            'long name' => [['name' => str_repeat('a', 256)], 'name', 'O nome deve ter no máximo 255 caracteres.'],
            'description type' => [['description' => ['bad']], 'description', 'Informe uma descrição válida.'],
            'long description' => [['description' => str_repeat('a', 5001)], 'description', 'A descrição deve ter no máximo 5000 caracteres.'],
            'price type' => [['price' => ['bad']], 'price', 'Informe um preço numérico.'],
            'price text' => [['price' => 'bad'], 'price', 'Informe um preço numérico.'],
            'negative price' => [['price' => '-0.01'], 'price', 'O preço não pode ser negativo.'],
            'price precision' => [['price' => '1.001'], 'price', 'Use no máximo duas casas decimais no preço.'],
            'scientific price' => [['price' => '1e2'], 'price', 'Use no máximo duas casas decimais no preço.'],
            'price overflow' => [['price' => '100000000'], 'price', 'O preço máximo é 99999999.99.'],
            'fractional stock' => [['stock_quantity' => '0.5'], 'stock_quantity', 'Informe uma quantidade inteira para o estoque.'],
            'stock type' => [['stock_quantity' => ['bad']], 'stock_quantity', 'Informe uma quantidade inteira para o estoque.'],
            'negative stock' => [['stock_quantity' => '-1'], 'stock_quantity', 'O estoque não pode ser negativo.'],
            'stock overflow' => [['stock_quantity' => '4294967296'], 'stock_quantity', 'O estoque máximo é 4294967295.'],
            'unknown availability' => [['availability_status' => 'bad'], 'availability_status', 'Selecione uma disponibilidade válida.'],
            'availability type' => [['availability_status' => ['AVAILABLE']], 'availability_status', 'Selecione uma disponibilidade válida.'],
            'active type' => [['active' => 'bad'], 'active', 'Informe um status válido para o produto.'],
        ];
    }

    #[DataProvider('validInventory')]
    public function test_accepts_inventory_boundaries_and_all_availability_values(string $price, ?string $stock, string $availability): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->post(route('products.store'), $this->payload([
            'price' => $price, 'stock_quantity' => $stock, 'availability_status' => $availability, 'active' => '0',
        ]))->assertRedirectToRoute('products.index')->assertSessionHasNoErrors();

        $product = $store->products()->sole();
        $this->assertSame($price, $product->price);
        $this->assertSame($stock === null || $stock === '' ? null : (int) $stock, $product->stock_quantity);
        $this->assertSame($availability, $product->availability_status->value);
        $this->assertFalse($product->active);
    }

    public static function validInventory(): array
    {
        return [
            'zero' => ['0.00', '0', 'OUT_OF_STOCK'],
            'maximum' => ['99999999.99', '4294967295', 'LOW_STOCK'],
            'null stock' => ['10.50', null, 'AVAILABLE'],
            'empty stock' => ['1.00', '', 'INACTIVE'],
        ];
    }

    public function test_creation_form_escapes_previous_input(): void
    {
        $store = Store::factory()->create();
        $name = '<script>name()</script>';
        $description = '</textarea><script>description()</script>';

        $this->actingAs($store->user)->withSession(['_old_input' => compact('name', 'description')])
            ->get(route('products.create'))->assertOk()
            ->assertSee($name)->assertDontSee($name, false)
            ->assertSee($description)->assertDontSee($description, false);
    }

    public function test_owner_can_edit_all_allowed_fields_without_changing_slug_or_store(): void
    {
        $store = Store::factory()->inactive()->create();
        $product = Product::factory()->for($store)->inactive()->create(['slug' => 'original']);
        $other = Product::factory()->create(['slug' => 'renamed']);
        $this->actingAs($store->user)->get(route('products.edit', $product))->assertOk()
            ->assertViewIs('products.edit')->assertSee($product->name)
            ->assertSee('name="_token"', false)->assertSee('name="_method" value="PUT"', false);

        $this->put(route('products.update', $product), $this->payload([
            'name' => 'Renamed', 'description' => 'Nova descrição', 'price' => '12.34',
            'stock_quantity' => '5', 'availability_status' => 'LOW_STOCK',
            'slug' => 'renamed', 'store_id' => $other->store_id, 'id' => $other->id,
        ]))->assertRedirectToRoute('products.edit', $product)->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('original', $product->slug);
        $this->assertSame($store->id, $product->store_id);
        $this->assertSame('Renamed', $product->name);
        $this->assertSame('Nova descrição', $product->description);
        $this->assertSame('12.34', $product->price);
        $this->assertSame(5, $product->stock_quantity);
        $this->assertSame(ProductAvailabilityStatus::LowStock, $product->availability_status);
        $this->assertTrue($product->active);
        $this->assertSame('renamed', $other->fresh()->slug);
    }

    public function test_invalid_update_preserves_product_and_returns_old_input(): void
    {
        $product = Product::factory()->create()->refresh();
        $original = $product->getAttributes();

        $this->actingAs($product->store->user)->from(route('products.edit', $product))
            ->put(route('products.update', $product), $this->payload(['name' => 'Alterado', 'price' => '-1']))
            ->assertRedirectToRoute('products.edit', $product)
            ->assertSessionHasErrors(['price' => 'O preço não pode ser negativo.'])
            ->assertSessionHasInput('name', 'Alterado');

        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    public function test_owner_can_clear_stock_and_deactivate_using_edit_form(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $this->actingAs($product->store->user)->patch(route('products.update', $product), $this->payload([
            'stock_quantity' => '', 'active' => '0',
        ]))->assertRedirectToRoute('products.edit', $product);

        $this->assertNull($product->fresh()->stock_quantity);
        $this->assertFalse($product->fresh()->active);
    }

    public function test_status_updates_are_idempotent_and_do_not_change_other_fields(): void
    {
        $product = Product::factory()->create([
            'name' => 'Mouse original', 'slug' => 'original', 'availability_status' => ProductAvailabilityStatus::LowStock,
        ])->refresh();
        $original = $product->getAttributes();
        unset($original['active'], $original['updated_at']);
        $other = Store::factory()->create();
        $this->actingAs($product->store->user);

        foreach (['0', '0', '1', '1'] as $active) {
            $this->patch(route('products.status', $product), [
                'active' => $active, 'name' => 'Injected', 'slug' => 'injected', 'store_id' => $other->id,
                'availability_status' => 'INACTIVE', 'stock_quantity' => 999, 'price' => 999,
            ])->assertRedirectToRoute('products.index')->assertSessionHas('status', $active === '1' ? 'Produto ativado.' : 'Produto desativado.');

            $this->assertSame($active === '1', $product->fresh()->active);
            $attributes = $product->fresh()->getAttributes();
            unset($attributes['active'], $attributes['updated_at']);
            $this->assertSame($original, $attributes);
        }
    }

    public function test_status_change_requires_a_valid_active_flag(): void
    {
        $product = Product::factory()->create()->refresh();
        $original = $product->getAttributes();
        $this->actingAs($product->store->user);

        $this->patch(route('products.status', $product), [])->assertSessionHasErrors([
            'active' => 'Informe o status do produto.',
        ]);
        $this->patch(route('products.status', $product), ['active' => 'bad'])->assertSessionHasErrors([
            'active' => 'Informe um status válido para o produto.',
        ]);

        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    #[DataProvider('editingEndpoints')]
    public function test_guest_cannot_access_product_editing(string $method, string $route): void
    {
        $product = Product::factory()->create()->refresh();
        $original = $product->getAttributes();

        $this->{$method}(route($route, $product), $this->payload())->assertRedirectToRoute('login');

        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    #[DataProvider('editingEndpoints')]
    public function test_other_merchant_cannot_access_product_editing(string $method, string $route): void
    {
        $product = Product::factory()->create()->refresh();
        $original = $product->getAttributes();
        $other = Store::factory()->create();

        $this->actingAs($other->user)->{$method}(route($route, $product), [])->assertNotFound();

        $this->assertSame($original, $product->fresh()->getAttributes());
    }

    #[DataProvider('editingEndpoints')]
    public function test_missing_product_returns_not_found(string $method, string $route): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->{$method}(route($route, 999), $this->payload())->assertNotFound();
    }

    public static function editingEndpoints(): array
    {
        return [
            'edit form' => ['get', 'products.edit'],
            'update' => ['put', 'products.update'],
            'activation' => ['patch', 'products.status'],
        ];
    }

    public function test_edit_form_escapes_persisted_text(): void
    {
        $product = Product::factory()->create([
            'name' => '<script>name()</script>',
            'description' => '</textarea><script>description()</script>',
            'slug' => '<script>slug()</script>',
        ]);

        $response = $this->actingAs($product->store->user)->get(route('products.edit', $product))->assertOk();

        foreach ([$product->name, $product->description, $product->slug] as $value) {
            $response->assertSee($value)->assertDontSee($value, false);
        }
    }

    /** @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function payload(array $changes = []): array
    {
        return array_replace([
            'name' => 'Mouse Logitech G305',
            'description' => 'Mouse sem fio.',
            'price' => '199.90',
            'availability_status' => 'AVAILABLE',
            'active' => '1',
        ], $changes);
    }

    public function test_confirmation_page_escapes_name_and_does_not_delete_product(): void
    {
        $product = Product::factory()->create(['name' => '<script>product()</script>']);

        $this->actingAs($product->store->user)->get(route('products.delete', $product))->assertOk()
            ->assertViewIs('products.delete')->assertSee($product->name)->assertDontSee($product->name, false)
            ->assertSee('name="_token"', false)->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('name="confirmed"', false)->assertSee('Cancelar')->assertSee(route('products.index'));

        $this->assertModelExists($product);
    }

    public function test_owner_can_delete_confirmed_product_without_deleting_other_records(): void
    {
        $store = Store::factory()->inactive()->create();
        $product = Product::factory()->for($store)->inactive()->create();
        $sibling = Product::factory()->for($store)->create();
        $other = Product::factory()->create();

        $this->actingAs($store->user)->delete(route('products.destroy', $product), [
            'confirmed' => '1', 'id' => $other->id, 'store_id' => $other->store_id,
        ])->assertRedirectToRoute('products.index')->assertSessionHas('status', 'Produto excluído com sucesso.');

        $this->assertModelMissing($product);
        $this->assertModelExists($sibling);
        $this->assertModelExists($other);
        $this->assertModelExists($store);
        $this->assertModelExists($store->user);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('store', fn (Store $store): bool => $store->products_count === 1);
        $this->get(route('products.edit', $product))->assertNotFound();
    }

    #[DataProvider('unconfirmedDeletion')]
    public function test_unconfirmed_deletion_preserves_product(array $payload): void
    {
        $product = Product::factory()->create();

        $this->actingAs($product->store->user)->from(route('products.delete', $product))
            ->delete(route('products.destroy', $product), $payload)
            ->assertRedirectToRoute('products.delete', $product)
            ->assertSessionHasErrors(['confirmed' => 'Confirme a exclusão do produto.']);

        $this->assertModelExists($product);
    }

    public static function unconfirmedDeletion(): array
    {
        return [
            'missing' => [[]],
            'declined' => [['confirmed' => '0']],
            'invalid' => [['confirmed' => 'anything']],
            'array' => [['confirmed' => ['1']]],
        ];
    }

    #[DataProvider('deletionEndpoints')]
    public function test_guest_cannot_access_product_deletion(string $method, string $route): void
    {
        $product = Product::factory()->create();

        $this->{$method}(route($route, $product), ['confirmed' => '1'])->assertRedirectToRoute('login');

        $this->assertModelExists($product);
    }

    #[DataProvider('deletionEndpoints')]
    public function test_other_merchant_cannot_access_product_deletion(string $method, string $route): void
    {
        $product = Product::factory()->create();
        $other = Store::factory()->create();

        $this->actingAs($other->user)->{$method}(route($route, $product), ['confirmed' => '1'])->assertNotFound();

        $this->assertModelExists($product);
    }

    #[DataProvider('deletionEndpoints')]
    public function test_missing_product_cannot_be_deleted(string $method, string $route): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->{$method}(route($route, 999), ['confirmed' => '1'])->assertNotFound();
    }

    public static function deletionEndpoints(): array
    {
        return [
            'confirmation' => ['get', 'products.delete'],
            'deletion' => ['delete', 'products.destroy'],
        ];
    }

    public function test_list_links_to_edit_and_delete_and_submits_explicit_status(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($product->store->user)->get(route('products.index'))->assertOk()
            ->assertSee(route('products.edit', $product))->assertSee(route('products.delete', $product))
            ->assertSee(route('products.status', $product))
            ->assertSee('name="_method" value="PATCH"', false)->assertSee('name="active" value="0"', false)
            ->assertSee('Desativar');
    }

    public function test_owner_can_create_product_with_shared_category(): void
    {
        $store = Store::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($store->user)->post(route('products.store'), $this->payload(['category_id' => $category->id]))
            ->assertRedirectToRoute('products.index')->assertSessionHasNoErrors();

        $this->assertSame($category->id, $store->products()->sole()->category_id);
        $this->get(route('products.index'))->assertOk()->assertSee($category->name);
    }

    public function test_owner_can_change_and_clear_category_without_changing_slug(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->for(Category::factory())->create();
        $slug = $product->slug;

        $this->actingAs($product->store->user)->put(route('products.update', $product), $this->payload(['category_id' => $category->id]))
            ->assertRedirectToRoute('products.edit', $product)->assertSessionHasNoErrors();

        $this->assertSame($category->id, $product->fresh()->category_id);
        $this->assertSame($slug, $product->fresh()->slug);
        $this->get(route('products.edit', $product))->assertOk()
            ->assertSee('value="'.$category->id.'" selected', false);

        $this->put(route('products.update', $product), $this->payload(['category_id' => '']))
            ->assertRedirectToRoute('products.edit', $product)->assertSessionHasNoErrors();

        $this->assertNull($product->fresh()->category_id);
    }

    public function test_omitted_category_preserves_existing_association(): void
    {
        $product = Product::factory()->for(Category::factory())->create();
        $categoryId = $product->category_id;

        $this->actingAs($product->store->user)->put(route('products.update', $product), $this->payload())
            ->assertRedirectToRoute('products.edit', $product);

        $this->assertSame($categoryId, $product->fresh()->category_id);
    }

    #[DataProvider('invalidCategories')]
    public function test_invalid_category_does_not_create_product(mixed $categoryId): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->post(route('products.store'), $this->payload(['category_id' => $categoryId]))
            ->assertSessionHasErrors(['category_id' => 'Selecione uma categoria válida.']);

        $this->assertDatabaseCount('products', 0);
    }

    public static function invalidCategories(): array
    {
        return ['missing record' => [999], 'not an integer' => ['invalid']];
    }

    public function test_invalid_category_update_preserves_existing_category(): void
    {
        $product = Product::factory()->for(Category::factory())->create();
        $categoryId = $product->category_id;

        $this->actingAs($product->store->user)->put(route('products.update', $product), $this->payload(['category_id' => 999]))
            ->assertSessionHasErrors('category_id');

        $this->assertSame($categoryId, $product->fresh()->category_id);
    }

    public function test_merchant_cannot_change_category_of_another_stores_product(): void
    {
        $product = Product::factory()->for(Category::factory())->create();
        $category = Category::factory()->create();
        $originalCategory = $product->category_id;
        $otherStore = Store::factory()->create();

        $this->actingAs($otherStore->user)->put(route('products.update', $product), $this->payload(['category_id' => $category->id]))
            ->assertNotFound();

        $this->assertSame($originalCategory, $product->fresh()->category_id);
    }

    public function test_category_labels_are_escaped_in_forms_and_list(): void
    {
        $category = Category::factory()->create(['name' => '<script>category()</script>']);
        $product = Product::factory()->for($category)->create();

        $this->actingAs($product->store->user)->get(route('products.create'))->assertOk()
            ->assertSee($category->name)->assertDontSee($category->name, false);
        $this->get(route('products.edit', $product))->assertOk()
            ->assertSee($category->name)->assertDontSee($category->name, false);
        $this->get(route('products.index'))->assertOk()
            ->assertSee($category->name)->assertDontSee($category->name, false);
    }
}
