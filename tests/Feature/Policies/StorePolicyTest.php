<?php

namespace Tests\Feature\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class StorePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_without_store_can_create_one(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('create', Store::class));
    }

    public function test_user_with_an_inactive_store_cannot_create_another(): void
    {
        $store = Store::factory()->inactive()->create();

        $response = Gate::forUser($store->user)->inspect('create', Store::class);

        $this->assertTrue($response->denied());
        $this->assertSame('Você já possui uma loja.', $response->message());
    }

    public function test_owner_can_update_their_store(): void
    {
        $store = Store::factory()->create();

        $this->assertTrue(Gate::forUser($store->user)->allows('update', $store));
    }

    public function test_other_user_cannot_update_store_and_gets_404(): void
    {
        $store = Store::factory()->create();
        $otherUser = User::factory()->create();

        $response = Gate::forUser($otherUser)->inspect('update', $store);

        $this->assertTrue($response->denied());
        $this->assertSame(404, $response->status());
    }

    public function test_guest_cannot_create_or_update_store(): void
    {
        $store = Store::factory()->create();

        $this->assertFalse(Gate::allows('create', Store::class));
        $this->assertFalse(Gate::allows('update', $store));
    }
}
