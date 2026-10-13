<?php

namespace Tests\Feature\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UpdateStoreRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_missing_required_fields_leave_store_unchanged(): void
    {
        $store = Store::factory()->create();
        $original = $store->fresh()->getAttributes();

        $this->actingAs($store->user)->from(route('stores.edit', $store))
            ->put(route('stores.update', $store), [])->assertRedirectToRoute('stores.edit', $store)
            ->assertSessionHasErrors([
                'name' => 'Informe o nome da loja.',
                'description' => 'Informe a descrição da loja.',
                'whatsapp' => 'Informe o WhatsApp da loja.',
                'city' => 'Informe a cidade.',
                'state' => 'Informe a UF.',
                'active' => 'Informe o status da loja.',
            ]);

        $this->assertSame($original, $store->fresh()->getAttributes());
    }

    public function test_invalid_update_uses_shared_validation_and_preserves_previous_input(): void
    {
        $store = Store::factory()->create();
        $original = $store->fresh()->getAttributes();
        $attributes = $store->only(['name', 'description', 'whatsapp', 'city', 'state', 'address', 'active']);
        $attributes['name'] = 'Novo nome';
        $attributes['state'] = 'ZZ';

        $this->actingAs($store->user)->from(route('stores.edit', $store))
            ->put(route('stores.update', $store), $attributes)->assertRedirectToRoute('stores.edit', $store)
            ->assertSessionHasErrors(['state' => 'Informe uma UF válida.'])
            ->assertSessionHasInput('name', 'Novo nome');

        $this->assertSame($original, $store->fresh()->getAttributes());
        $this->get(route('stores.edit', $store))->assertOk()->assertSee('value="Novo nome"', false);
    }
}
