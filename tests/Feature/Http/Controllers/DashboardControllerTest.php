<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_user_without_store_sees_creation_action_and_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertViewIs('dashboard')
            ->assertViewHas('store', null)->assertSee('Você ainda não possui uma loja.')
            ->assertSee(route('stores.create'))->assertSee(route('logout'))->assertSee('Sair')
            ->assertDontSee('Editar loja');
    }

    #[TestWith([true, 'Ativa'])]
    #[TestWith([false, 'Inativa'])]
    public function test_dashboard_shows_only_own_store_and_its_total_product_count(bool $active, string $status): void
    {
        $store = Store::factory()->create([
            'name' => 'Tech Bahia', 'slug' => 'tech-bahia', 'active' => $active,
        ]);
        Product::factory()->for($store)->create();
        Product::factory()->for($store)->inactive()->create();
        $otherStore = Store::factory()->has(Product::factory()->count(3))->create(['name' => 'Outra Loja']);

        $this->actingAs($store->user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('store', fn (Store $shown): bool => $shown->id === $store->id && $shown->products_count === 2)
            ->assertSee('Tech Bahia')->assertSee('tech-bahia')->assertSee($status)
            ->assertSee('Salvador / BA')->assertSee('Produtos cadastrados')
            ->assertSee(route('stores.edit', $store))->assertSee(route('logout'))
            ->assertDontSee(route('stores.create'))->assertDontSee($otherStore->name);
    }

    public function test_dashboard_shows_zero_products_for_empty_store(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('store', fn (Store $shown): bool => $shown->products_count === 0);
    }

    public function test_dashboard_escapes_store_and_user_names(): void
    {
        $dangerous = '<script>alert(1)</script>';
        $user = User::factory()->create(['name' => $dangerous]);
        Store::factory()->for($user)->create(['name' => $dangerous, 'city' => $dangerous]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee($dangerous)->assertDontSee($dangerous, false);
    }

    public function test_guest_login_returns_to_requested_dashboard(): void
    {
        $user = User::factory()->create();
        $this->get(route('dashboard'))->assertRedirectToRoute('login');

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('dashboard');

        $this->get(route('dashboard'))->assertOk();
        $this->post(route('logout'))->assertRedirectToRoute('login');
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }
}
