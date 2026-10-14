<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Actions\DeleteAccount;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VerifiedMerchantAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('merchantEndpoints')]
    public function test_unverified_account_cannot_access_any_merchant_endpoint(string $method, string $name, array $parameterNames): void
    {
        $user = User::factory()->unverified()->create(['created_at' => now()->subYear()]);
        $store = Store::factory()->for($user)->create();
        $product = Product::factory()->for($store)->create();
        $image = ProductImage::factory()->for($product)->create();
        $parameters = array_intersect_key(compact('store', 'product', 'image'), array_flip($parameterNames));

        $this->actingAs($user)->call($method, route($name, $parameters))->assertRedirectToRoute('verification.notice');

        $this->assertModelExists($user);
        $this->assertModelExists($store);
        $this->assertModelExists($product);
        $this->assertModelExists($image);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /** @return array<string, array{string, string, list<string>}> */
    public static function merchantEndpoints(): array
    {
        return [
            'dashboard' => ['GET', 'dashboard', []],
            'create store form' => ['GET', 'stores.create', []],
            'create store' => ['POST', 'stores.store', []],
            'edit store form' => ['GET', 'stores.edit', ['store']],
            'update store put' => ['PUT', 'stores.update', ['store']],
            'update store patch' => ['PATCH', 'stores.update', ['store']],
            'store image upload' => ['POST', 'stores.image.store', ['store']],
            'store private image' => ['GET', 'stores.image.show', ['store']],
            'store image removal' => ['DELETE', 'stores.image.destroy', ['store']],
            'list products' => ['GET', 'products.index', []],
            'create product form' => ['GET', 'products.create', []],
            'create product' => ['POST', 'products.store', []],
            'edit product form' => ['GET', 'products.edit', ['product']],
            'update product put' => ['PUT', 'products.update', ['product']],
            'update product patch' => ['PATCH', 'products.update', ['product']],
            'update product status' => ['PATCH', 'products.status', ['product']],
            'delete product confirmation' => ['GET', 'products.delete', ['product']],
            'delete product' => ['DELETE', 'products.destroy', ['product']],
            'product image upload' => ['POST', 'products.images.store', ['product']],
            'product private image' => ['GET', 'products.images.show', ['product', 'image']],
            'product cover update' => ['PATCH', 'products.images.cover', ['product', 'image']],
            'product image removal' => ['DELETE', 'products.images.destroy', ['product', 'image']],
        ];
    }

    public function test_verified_user_can_access_dashboard_and_manage_existing_store(): void
    {
        $user = User::factory()->create();
        $store = Store::factory()->for($user)->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->get(route('stores.edit', $store))->assertOk();
        $this->get(route('products.index'))->assertOk();
    }

    public function test_unverified_json_request_is_forbidden(): void
    {
        $this->actingAs(User::factory()->unverified()->create())->getJson(route('dashboard'))->assertForbidden();
    }

    public function test_existing_unverified_user_can_log_in_and_log_out_without_being_verified(): void
    {
        $user = User::factory()->unverified()->create(['created_at' => now()->subYear()]);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirectToRoute('verification.notice');

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->post(route('logout'))->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    public function test_unverified_user_can_still_delete_own_account(): void
    {
        Storage::fake(DeleteAccount::BACKUP_DISK);
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('account.delete'))->assertOk();
        $this->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home')->assertSessionHasNoErrors();

        $this->assertModelMissing($user);
        $this->assertGuest();
    }

    public function test_public_pages_remain_available_for_guest_and_unverified_user_with_existing_store(): void
    {
        $storeDisk = Storage::fake(Store::IMAGE_DISK);
        $productDisk = Storage::fake(ProductImage::DISK);
        $user = User::factory()->unverified()->create();
        $store = Store::factory()->for($user)->create(['logo' => 'logo.jpg']);
        $product = Product::factory()->for($store)->create();
        $image = ProductImage::factory()->for($product)->create();
        $storeDisk->put($store->logo, file_get_contents(base_path('tests/Fixtures/product.jpg')));
        $productDisk->put($image->image_path, file_get_contents(base_path('tests/Fixtures/product.jpg')));
        $urls = [
            route('home'),
            route('home', ['tipo' => 'produtos']),
            route('public.stores.show', $store->slug),
            route('public.products.show', [$store->slug, $product->slug]),
            route('public.stores.image', $store->slug),
            route('public.products.images.show', [$store->slug, $product->slug, $image]),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($user);
        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }

        $this->assertNull($user->fresh()->email_verified_at);
        $storeDisk->assertExists($store->logo);
        $productDisk->assertExists($image->image_path);
    }
}
