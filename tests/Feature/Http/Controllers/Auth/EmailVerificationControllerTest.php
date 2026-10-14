<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unverified_user_sees_notice_with_own_address_and_resend_form(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))->assertOk()->assertViewIs('auth.verify-email')
            ->assertSee($user->email)->assertSee('Verifique seu e-mail')
            ->assertSee(route('verification.send'))->assertSee('name="_token"', false)
            ->assertSee(route('account.delete'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verified_user_is_redirected_from_notice_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get(route('verification.notice'))->assertRedirectToRoute('dashboard');
    }

    public function test_native_notification_contains_expiring_signed_link_that_verifies_email(): void
    {
        $this->freezeTime();
        $user = User::factory()->unverified()->create();
        $mail = (new VerifyEmail)->toMail($user);
        parse_str(parse_url($mail->actionUrl, PHP_URL_QUERY), $parameters);
        $this->assertSame(now()->addHour()->timestamp, (int) $parameters['expires']);
        $this->assertArrayHasKey('signature', $parameters);
        $this->assertSame('Confirme seu e-mail — AL Commerce', $mail->subject);
        Event::fake([Verified::class]);

        $this->actingAs($user)->get($mail->actionUrl)->assertRedirectToRoute('dashboard')
            ->assertSessionHas('status', 'E-mail verificado. Sua conta está pronta para gerenciar sua loja.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmail)->toMail($user)->actionUrl.'&tampered=1';

        $this->actingAs($user)->get($url)->assertForbidden()->assertViewIs('auth.verification-error')
            ->assertSee('O link é inválido, expirou ou pertence a outra conta.')
            ->assertSee(route('verification.notice'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unsigned_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_link_is_rejected(): void
    {
        $this->freezeTime();
        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmail)->toMail($user)->actionUrl;
        $this->travel(61)->minutes();

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_valid_signature_with_wrong_email_hash_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('other@example.test')]);

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_user_cannot_verify_another_accounts_signed_link(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $url = (new VerifyEmail)->toMail($other)->actionUrl;

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    public function test_old_link_does_not_verify_a_different_email_address(): void
    {
        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmail)->toMail($user)->actionUrl;
        $user->update(['email' => 'changed@example.test']);

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_revisiting_valid_link_does_not_change_verified_timestamp_or_repeat_event(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()->subDay()]);
        $verifiedAt = $user->email_verified_at;
        Event::fake([Verified::class]);

        $this->actingAs($user)->get((new VerifyEmail)->toMail($user)->actionUrl)->assertRedirectToRoute('dashboard');

        $this->assertTrue($user->fresh()->email_verified_at->equalTo($verifiedAt));
        Event::assertNotDispatched(Verified::class);
    }

    public function test_guest_must_log_in_and_then_returns_to_original_verification_link(): void
    {
        $user = User::factory()->unverified()->create();
        $url = (new VerifyEmail)->toMail($user)->actionUrl;
        $this->get(route('verification.notice'))->assertRedirectToRoute('login');
        $this->get($url)->assertRedirectToRoute('login');
        $this->assertNull($user->fresh()->email_verified_at);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect($url);
        $this->get($url)->assertRedirectToRoute('dashboard');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_returns_to_previously_requested_management_page(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('stores.create'))->assertRedirectToRoute('verification.notice');

        $this->get((new VerifyEmail)->toMail($user)->actionUrl)->assertRedirectToRoute('stores.create');

        $this->get(route('stores.create'))->assertOk();
    }

    public function test_invalid_json_verification_keeps_json_error_response(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->getJson(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden()->assertJsonStructure(['message']);

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
