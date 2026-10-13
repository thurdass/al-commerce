<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_view_creation_form(): void
    {
        $this->actingAs(User::factory()->create())->get(route('stores.create'))
            ->assertOk()->assertViewIs('stores.create')->assertSee('name="_token"', false);
    }

    public function test_authenticated_user_creates_store_owned_by_their_account(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)->post(route('stores.store'), $this->payload([
            'user_id' => $otherUser->id,
            'slug' => 'injected-slug',
            'category' => 'Injected category',
            'logo' => '/injected.png',
            'state' => 'ba',
        ]))->assertRedirectToRoute('home')->assertSessionHasNoErrors();

        $store = Store::query()->sole();
        $this->assertSame($owner->id, $store->user_id);
        $this->assertSame('tech-bahia', $store->slug);
        $this->assertSame('BA', $store->state);
        $this->assertNull($store->category);
        $this->assertNull($store->logo);
        $this->assertNull($store->address);
        $this->assertTrue($store->active);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id, 'name' => 'Tech Bahia',
            'description' => 'Produtos de tecnologia no comércio local.',
            'whatsapp' => '5571999999999', 'city' => 'Salvador',
        ]);
        $this->assertNull($otherUser->fresh()->store);
    }

    #[TestWith(['Café São João', 'cafe-sao-joao'])]
    #[TestWith(['!!!', 'loja'])]
    #[TestWith(['🎉', 'loja'])]
    public function test_generates_url_safe_slug_from_store_name(string $name, string $expected): void
    {
        $this->actingAs(User::factory()->create())->post(route('stores.store'), $this->payload(['name' => $name]))
            ->assertRedirectToRoute('home');

        $this->assertSame($expected, Store::query()->sole()->slug);
    }

    public function test_generates_unique_slug_even_when_a_suffixed_slug_is_taken(): void
    {
        Store::factory()->create(['slug' => 'tech-bahia']);
        Store::factory()->create(['slug' => 'tech-bahia-2']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('stores.store'), $this->payload())->assertRedirectToRoute('home');

        $this->assertSame('tech-bahia-3', $user->fresh()->store->slug);
    }

    public function test_long_name_with_slug_collision_fits_database_column(): void
    {
        Store::factory()->create(['slug' => str_repeat('a', 240)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('stores.store'), $this->payload(['name' => str_repeat('a', 255)]))
            ->assertRedirectToRoute('home');

        $this->assertSame(str_repeat('a', 240).'-2', $user->fresh()->store->slug);
    }

    public function test_guest_cannot_view_creation_form_or_create_store(): void
    {
        $this->get(route('stores.create'))->assertRedirectToRoute('login');
        $this->post(route('stores.store'), $this->payload())->assertRedirectToRoute('login');

        $this->assertDatabaseCount('stores', 0);
    }

    public function test_user_with_store_cannot_view_creation_form_or_create_another_store(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->get(route('stores.create'))->assertForbidden();
        $this->post(route('stores.store'), $this->payload())->assertForbidden();

        $this->assertDatabaseCount('stores', 1);
        $this->assertModelExists($store);
    }

    public function test_store_can_be_created_inactive_with_an_address(): void
    {
        $this->actingAs(User::factory()->create())->post(route('stores.store'), $this->payload([
            'active' => '0', 'address' => 'Rua Bahia, 10',
        ]))->assertRedirectToRoute('home');

        $store = Store::query()->sole();
        $this->assertFalse($store->active);
        $this->assertSame('Rua Bahia, 10', $store->address);
    }

    public function test_missing_required_fields_do_not_create_a_store(): void
    {
        $this->actingAs(User::factory()->create())->post(route('stores.store'), [])
            ->assertSessionHasErrors([
                'name' => 'Informe o nome da loja.',
                'description' => 'Informe a descrição da loja.',
                'whatsapp' => 'Informe o WhatsApp da loja.',
                'city' => 'Informe a cidade.',
                'state' => 'Informe a UF.',
                'active' => 'Informe o status da loja.',
            ]);

        $this->assertDatabaseCount('stores', 0);
    }

    #[DataProvider('invalidStoreData')]
    public function test_invalid_data_does_not_create_a_store(array $changes, string $field, string $message): void
    {
        $this->actingAs(User::factory()->create())->from(route('stores.create'))
            ->post(route('stores.store'), $this->payload($changes))
            ->assertRedirectToRoute('stores.create')->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('stores', 0);
    }

    public static function invalidStoreData(): array
    {
        return [
            'name type' => [['name' => ['bad']], 'name', 'Informe um nome válido.'],
            'long name' => [['name' => str_repeat('a', 256)], 'name', 'O nome deve ter no máximo 255 caracteres.'],
            'description type' => [['description' => ['bad']], 'description', 'Informe uma descrição válida.'],
            'long description' => [['description' => str_repeat('a', 5001)], 'description', 'A descrição deve ter no máximo 5000 caracteres.'],
            'invalid phone' => [['whatsapp' => 'phone'], 'whatsapp', 'Use de 10 a 15 dígitos, incluindo o código do país e o DDD.'],
            'long phone' => [['whatsapp' => str_repeat('5', 16)], 'whatsapp', 'Use de 10 a 15 dígitos, incluindo o código do país e o DDD.'],
            'phone type' => [['whatsapp' => ['55']], 'whatsapp', 'Informe um número válido.'],
            'city type' => [['city' => ['bad']], 'city', 'Informe uma cidade válida.'],
            'long city' => [['city' => str_repeat('a', 256)], 'city', 'A cidade deve ter no máximo 255 caracteres.'],
            'invalid state' => [['state' => 'ZZ'], 'state', 'Informe uma UF válida.'],
            'state type' => [['state' => ['BA']], 'state', 'Informe uma UF válida.'],
            'address type' => [['address' => ['bad']], 'address', 'Informe um endereço válido.'],
            'long address' => [['address' => str_repeat('a', 256)], 'address', 'O endereço deve ter no máximo 255 caracteres.'],
            'invalid active flag' => [['active' => 'anything'], 'active', 'Informe um status válido para a loja.'],
        ];
    }

    public function test_creation_form_escapes_previous_input(): void
    {
        $name = '<script>alert(1)</script>';

        $this->actingAs(User::factory()->create())->withSession(['_old_input' => ['name' => $name]])
            ->get(route('stores.create'))->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    /** @return array<string, mixed> */
    private function payload(array $changes = []): array
    {
        return array_replace([
            'name' => 'Tech Bahia',
            'description' => 'Produtos de tecnologia no comércio local.',
            'whatsapp' => '5571999999999',
            'city' => 'Salvador',
            'state' => 'BA',
            'active' => '1',
        ], $changes);
    }
}
