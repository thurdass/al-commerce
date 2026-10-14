<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisteredUserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_view_registration_form(): void
    {
        $this->get(route('register'))->assertOk()->assertViewIs('auth.register')
            ->assertSee('name="_token"', false)->assertSee('Criar conta de lojista');
    }

    public function test_registration_creates_user_with_hashed_password_and_authenticates(): void
    {
        Notification::fake();
        $this->withSession(['marker' => 'before-login']);
        $sessionId = session()->getId();

        $response = $this->post(route('register.store'), $this->payload([
            'email' => ' MARIA@EXAMPLE.COM ',
            'email_verified_at' => now()->toDateTimeString(),
            'id' => 999,
        ]));

        $response->assertRedirectToRoute('verification.notice')->assertSessionHasNoErrors();
        $user = User::query()->sole();
        $this->assertSame('maria@example.com', $user->email);
        $this->assertSame('Maria Santos', $user->name);
        $this->assertTrue(Hash::check('senha-segura', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertNotSame(999, $user->id);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertDatabaseCount('stores', 0);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_existing_email_ignoring_case(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $this->from(route('register'))->post(route('register.store'), $this->payload([
            'email' => 'MARIA@EXAMPLE.COM',
        ]))->assertRedirectToRoute('register')->assertSessionHasErrors([
            'email' => 'Este e-mail já está cadastrado.',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    #[DataProvider('invalidRegistrationData')]
    public function test_registration_rejects_invalid_data(array $changes, string $field, string $message): void
    {
        $this->from(route('register'))->post(route('register.store'), $this->payload($changes))
            ->assertRedirectToRoute('register')->assertSessionHasErrors([$field => $message]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertNull(session('_old_input.password'));
        $this->assertNull(session('_old_input.password_confirmation'));
    }

    public static function invalidRegistrationData(): array
    {
        return [
            'missing name' => [['name' => ''], 'name', 'Informe seu nome.'],
            'invalid name' => [['name' => []], 'name', 'Informe seu nome.'],
            'long name' => [['name' => str_repeat('a', 256)], 'name', 'O nome deve ter no máximo 255 caracteres.'],
            'missing email' => [['email' => ''], 'email', 'Informe seu e-mail.'],
            'invalid email' => [['email' => 'invalid'], 'email', 'Informe um e-mail válido.'],
            'email as array' => [['email' => ['a']], 'email', 'Informe um e-mail válido.'],
            'long email' => [['email' => str_repeat('a', 250).'@example.com'], 'email', 'O e-mail deve ter no máximo 255 caracteres.'],
            'missing password' => [['password' => ''], 'password', 'Informe uma senha.'],
            'short password' => [['password' => '1234567'], 'password', 'A senha deve ter pelo menos 8 caracteres.'],
            'long password' => [['password' => str_repeat('a', 73)], 'password', 'A senha deve ter no máximo 72 caracteres.'],
            'long multibyte password' => [['password' => str_repeat('é', 40), 'password_confirmation' => str_repeat('é', 40)], 'password', 'A senha é muito longa. Use uma senha menor.'],
            'null byte in password' => [['password' => "senha\0secreta", 'password_confirmation' => "senha\0secreta"], 'password', 'A senha contém caracteres inválidos.'],
            'confirmation mismatch' => [['password_confirmation' => 'different'], 'password', 'A confirmação da senha não confere.'],
        ];
    }

    public function test_authenticated_user_cannot_register_another_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('register'))->assertRedirect();
        $this->post(route('register.store'), $this->payload())->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_form_escapes_previous_input(): void
    {
        $name = '<script>alert(1)</script>';

        $this->withSession(['_old_input' => ['name' => $name]])->get(route('register'))
            ->assertOk()->assertSee($name)->assertDontSee($name, false);
    }

    public function test_registration_accepts_multibyte_password_within_bcrypt_limit(): void
    {
        Notification::fake();
        $password = str_repeat('é', 36);

        $this->post(route('register.store'), $this->payload([
            'password' => $password, 'password_confirmation' => $password,
        ]))->assertRedirectToRoute('verification.notice')->assertSessionHasNoErrors();

        $user = User::query()->sole();
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @return array<string, mixed> */
    private function payload(array $changes = []): array
    {
        return array_replace([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
        ], $changes);
    }
}
