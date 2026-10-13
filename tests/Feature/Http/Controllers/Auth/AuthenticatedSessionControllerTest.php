<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_view_login_form(): void
    {
        $this->get(route('login'))->assertOk()->assertViewIs('auth.login')
            ->assertSee('name="_token"', false)->assertSee('Entrar na sua conta');
    }

    public function test_valid_credentials_authenticate_and_regenerate_session(): void
    {
        $user = User::factory()->create(['email' => 'maria@example.com']);
        $this->withSession(['marker' => 'before-login']);
        $sessionId = session()->getId();

        $this->post(route('login.store'), [
            'email' => ' MARIA@EXAMPLE.COM ',
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_login_redirects_to_the_intended_page(): void
    {
        $user = User::factory()->create();
        $destination = route('home').'?retorno=1';

        $this->withSession(['url.intended' => $destination])->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($destination);

        $this->assertAuthenticatedAs($user);
    }

    #[TestWith(['wrong-password', 'maria@example.com'])]
    #[TestWith(['password', 'unknown@example.com'])]
    public function test_invalid_credentials_do_not_authenticate(string $password, string $email): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $this->from(route('login'))->post(route('login.store'), compact('email', 'password'))
            ->assertRedirectToRoute('login')->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);

        $this->assertGuest();
        $this->assertNull(session('_old_input.password'));
    }

    public function test_missing_login_fields_are_rejected(): void
    {
        $this->post(route('login.store'), [])->assertSessionHasErrors([
            'email' => 'Informe seu e-mail.',
            'password' => 'Informe sua senha.',
        ]);

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_limited_and_expire_after_a_minute(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
        }

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Muitas tentativas. Tente novamente em 60 segundos.']);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_clears_previous_failed_attempts(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('dashboard');
        $this->post(route('logout'))->assertRedirectToRoute('login');

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('dashboard')->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_ends_authentication_and_invalidates_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['marker' => 'private', '_token' => 'old-token']);
        $sessionId = session()->getId();

        $this->post(route('logout'))->assertRedirectToRoute('login')->assertSessionMissing('marker');

        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame('old-token', session()->token());
    }

    public function test_logout_requires_authentication_and_post_method(): void
    {
        $this->post(route('logout'))->assertRedirectToRoute('login');
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('logout'))->assertMethodNotAllowed();
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_cannot_open_or_submit_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect();
        $this->post(route('login.store'), [])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }
}
