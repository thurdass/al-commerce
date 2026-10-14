<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailVerificationNotificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unverified_user_can_resend_only_to_own_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'), ['email' => $other->email, 'user_id' => $other->id])
            ->assertRedirectToRoute('verification.notice')->assertSessionHasNoErrors()
            ->assertSessionHas('verification-link-sent', true);

        Notification::assertSentTo($user, VerifyEmail::class);
        Notification::assertNotSentTo($other, VerifyEmail::class);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->get(route('verification.notice'))->assertOk()->assertSee('Reenviar novamente')
            ->assertSee('Enviamos um novo link de verificação.');
    }

    public function test_verified_user_does_not_receive_another_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirectToRoute('dashboard');

        Notification::assertNothingSent();
    }

    public function test_guest_cannot_resend_and_get_cannot_send_email(): void
    {
        Notification::fake();
        $this->post(route('verification.send'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->unverified()->create())->get(route('verification.send'))->assertMethodNotAllowed();

        Notification::assertNothingSent();
    }

    public function test_resend_requires_csrf_protection(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $this->app->instance('env', 'production');

        $this->actingAs($user)->post(route('verification.send'))->assertStatus(419);

        Notification::assertNothingSent();
    }

    public function test_resends_are_limited_per_account_and_limit_expires(): void
    {
        Notification::fake();
        $this->freezeTime();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post(route('verification.send'))->assertRedirectToRoute('verification.notice');
        }

        $this->post(route('verification.send'))->assertTooManyRequests()->assertSee('Muitos pedidos de verificação.');

        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
        $other = User::factory()->unverified()->create();
        $this->actingAs($other)->post(route('verification.send'))->assertRedirectToRoute('verification.notice');
        Notification::assertSentTo($other, VerifyEmail::class);
        $this->travel(61)->seconds();
        $this->actingAs($user)->post(route('verification.send'))->assertRedirectToRoute('verification.notice');
        Notification::assertSentToTimes($user, VerifyEmail::class, 7);
    }

    public function test_transport_failure_keeps_account_unverified_and_allows_retry(): void
    {
        $user = User::factory()->unverified()->create();
        $this->mock(Dispatcher::class)->shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        Exceptions::fake();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirectToRoute('verification.notice')
            ->assertSessionHasErrors(['verification' => 'Não conseguimos enviar o e-mail agora. Tente novamente em instantes.']);

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Exceptions::assertReported(TransportException::class);
    }

    public function test_registration_mail_failure_preserves_new_account_and_opens_verification_notice(): void
    {
        $this->mock(Dispatcher::class)->shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));
        Exceptions::fake();

        $this->post(route('register.store'), [
            'name' => 'Maria', 'email' => 'maria@example.test', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirectToRoute('verification.notice')
            ->assertSessionHasErrors(['verification' => 'Sua conta foi criada, mas não conseguimos enviar o e-mail. Tente reenviar em instantes.']);

        $user = User::query()->sole();
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Exceptions::assertReported(TransportException::class);
    }
}
