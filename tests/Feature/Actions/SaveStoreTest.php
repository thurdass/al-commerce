<?php

namespace Tests\Feature\Actions;

use App\Actions\SaveStore;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaveStoreTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_validation_error_if_owner_already_has_a_store_at_insert_time(): void
    {
        $store = Store::factory()->create();
        $newStore = $store->user->store()->make();

        try {
            app(SaveStore::class)->handle($newStore, Store::factory()->for($store->user)->raw());
            $this->fail('A second store must not be created.');
        } catch (ValidationException $exception) {
            $this->assertSame(['name' => ['Você já possui uma loja.']], $exception->errors());
            $this->assertDatabaseCount('stores', 1);
        }
    }

    public function test_retries_a_slug_collision_detected_during_insert(): void
    {
        Store::factory()->create(['slug' => 'taken']);
        $user = User::factory()->create();
        $collide = true;
        Store::creating(function (Store $store) use (&$collide): void {
            if ($collide) {
                $store->slug = 'taken';
                $collide = false;
            }
        });

        $store = app(SaveStore::class)->handle($user->store()->make(), Store::factory()->for($user)->raw([
            'name' => 'Tech Bahia',
        ]));

        $this->assertSame('tech-bahia', $store->fresh()->slug);
        $this->assertDatabaseCount('stores', 2);
    }

    public function test_persistent_slug_collisions_return_validation_error_without_partial_store(): void
    {
        Store::factory()->create(['slug' => 'taken']);
        $user = User::factory()->create();
        Store::creating(function (Store $store): void {
            $store->slug = 'taken';
        });

        try {
            app(SaveStore::class)->handle($user->store()->make(), Store::factory()->for($user)->raw());
            $this->fail('A conflicting slug must not be persisted.');
        } catch (ValidationException $exception) {
            $this->assertSame(['name' => ['Não foi possível reservar um slug. Tente novamente.']], $exception->errors());
            $this->assertNull($user->fresh()->store);
            $this->assertDatabaseCount('stores', 1);
        }
    }

    public function test_retries_a_slug_collision_detected_during_update(): void
    {
        Store::factory()->create(['slug' => 'taken']);
        $store = Store::factory()->create();
        $collide = true;
        Store::updating(function (Store $store) use (&$collide): void {
            if ($collide) {
                $store->slug = 'taken';
                $collide = false;
            }
        });

        app(SaveStore::class)->handle($store, ['name' => 'Nova Loja']);

        $this->assertSame('nova-loja', $store->fresh()->slug);
        $this->assertDatabaseCount('stores', 2);
    }

    public function test_repeated_update_collisions_do_not_persist_partial_changes(): void
    {
        Store::factory()->create(['slug' => 'taken']);
        $store = Store::factory()->create()->refresh();
        $original = $store->getAttributes();
        Store::updating(function (Store $store): void {
            $store->slug = 'taken';
        });

        try {
            app(SaveStore::class)->handle($store, ['name' => 'Novo Nome', 'city' => 'Outra Cidade']);
            $this->fail('A conflicting slug must not be persisted.');
        } catch (ValidationException $exception) {
            $this->assertSame(['name' => ['Não foi possível reservar um slug. Tente novamente.']], $exception->errors());
            $this->assertSame($original, $store->fresh()->getAttributes());
        }
    }
}
