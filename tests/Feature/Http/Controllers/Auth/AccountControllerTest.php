<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Actions\DeleteAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_delete_own_account_and_end_the_session(): void
    {
        $backups = Storage::fake(DeleteAccount::BACKUP_DISK);
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['marker' => 'private', '_token' => 'old-token']);
        $sessionId = session()->getId();

        $this->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home')->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Sua conta foi excluída permanentemente.')
            ->assertSessionMissing('marker');

        $this->assertModelMissing($user);
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('old-token', session()->token());
        $backups->assertDirectoryEmpty('/');
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    #[TestWith(['wrong', 'EXCLUIR', 'password', 'A senha atual está incorreta.'])]
    #[TestWith(['password', 'excluir', 'confirmation', 'Digite EXCLUIR para confirmar.'])]
    #[TestWith(['password', 'SIM', 'confirmation', 'Digite EXCLUIR para confirmar.'])]
    public function test_invalid_credentials_or_confirmation_preserve_account(string $password, string $confirmation, string $field, string $message): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('dashboard'))->delete(route('account.destroy'), compact('password', 'confirmation'))
            ->assertRedirectToRoute('dashboard')->assertSessionHasErrors([$field => $message]);

        $this->assertModelExists($user);
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('_old_input.password'));
    }

    public function test_both_fields_are_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete(route('account.destroy'))->assertSessionHasErrors([
            'password' => 'Informe sua senha atual.',
            'confirmation' => 'Digite EXCLUIR para confirmar.',
        ]);

        $this->assertModelExists($user);
    }

    #[TestWith(['password', ['password'], 'Informe uma senha válida.'])]
    #[TestWith(['confirmation', ['EXCLUIR'], 'Digite EXCLUIR para confirmar.'])]
    public function test_non_string_fields_are_rejected(string $field, array $value, string $message): void
    {
        $user = User::factory()->create();
        $payload = ['password' => 'password', 'confirmation' => 'EXCLUIR', $field => $value];

        $this->actingAs($user)->delete(route('account.destroy'), $payload)->assertSessionHasErrors([$field => $message]);

        $this->assertModelExists($user);
    }

    public function test_bcrypt_truncated_password_cannot_authorize_deletion(): void
    {
        $user = User::factory()->create(['password' => str_repeat('a', 72)]);

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => str_repeat('a', 73), 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors(['password' => 'A senha é muito longa. Use uma senha menor.']);

        $this->assertModelExists($user);
    }

    public function test_guest_cannot_delete_an_account(): void
    {
        $user = User::factory()->create();

        $this->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('login');

        $this->assertModelExists($user);
    }

    #[TestWith(['get'])]
    #[TestWith(['post'])]
    #[TestWith(['patch'])]
    public function test_deletion_requires_delete_method(string $method): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->{$method}(route('account.destroy'))->assertMethodNotAllowed();

        $this->assertModelExists($user);
    }

    #[TestWith(['id'])]
    #[TestWith(['user_id'])]
    #[TestWith(['store_id'])]
    public function test_client_cannot_select_another_account(string $field): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->delete(route('account.destroy'), [
            'password' => 'password', 'confirmation' => 'EXCLUIR', $field => $other->id,
        ])->assertSessionHasErrors([$field => 'A exclusão é permitida apenas para a conta autenticada.']);

        $this->assertModelExists($user);
        $this->assertModelExists($other);
    }

    public function test_another_users_password_cannot_authorize_deletion(): void
    {
        $user = User::factory()->create(['password' => 'own-password']);
        $other = User::factory()->create(['password' => 'other-password']);

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'other-password', 'confirmation' => 'EXCLUIR'])
            ->assertSessionHasErrors(['password' => 'A senha atual está incorreta.']);

        $this->assertModelExists($user);
        $this->assertModelExists($other);
    }

    public function test_user_sessions_and_reset_token_are_removed_without_affecting_other_users(): void
    {
        Storage::fake(DeleteAccount::BACKUP_DISK);
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach ([$user, $other] as $owner) {
            DB::table('sessions')->insert(['id' => 'session-'.$owner->id, 'user_id' => $owner->id, 'payload' => 'private', 'last_activity' => time()]);
            DB::table('password_reset_tokens')->insert(['email' => $owner->email, 'token' => 'token', 'created_at' => now()]);
        }

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home');

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseHas('sessions', ['user_id' => $other->id]);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $other->email]);
    }

    public function test_deletion_requires_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->app->instance('env', 'production');

        $this->actingAs($user)->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertStatus(419);

        $this->assertModelExists($user);
    }

    public function test_database_session_is_destroyed_and_replaced_with_guest_session(): void
    {
        Storage::fake(DeleteAccount::BACKUP_DISK);
        config(['session.driver' => 'database']);
        $this->app['session']->forgetDrivers();
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['marker' => 'private', '_token' => 'old-token']);
        $sessionId = session()->getId();

        $this->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])
            ->assertRedirectToRoute('home')->assertSessionMissing('marker');

        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => session()->getId(), 'user_id' => null]);
        $this->assertNotSame('old-token', session()->token());
    }

    public function test_repeated_password_attempts_are_limited(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->delete(route('account.destroy'), ['password' => 'wrong', 'confirmation' => 'EXCLUIR'])
                ->assertSessionHasErrors('password');
        }

        $this->delete(route('account.destroy'), ['password' => 'password', 'confirmation' => 'EXCLUIR'])->assertTooManyRequests();

        $this->assertModelExists($user);
    }
}
