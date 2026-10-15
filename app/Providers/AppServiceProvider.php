<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useHotFile(config('app.vite_hot_file'));

        VerifyEmail::toMailUsing(function (User $user, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Confirme seu e-mail — AL Commerce')
                ->greeting('Olá, '.$user->name.'!')
                ->line('Confirme seu endereço de e-mail para acessar o painel e gerenciar sua loja no AL Commerce.')
                ->action('Verificar meu e-mail', $url)
                ->line('Este link expira em '.config('auth.verification.expire').' minutos.')
                ->line('Se você não criou esta conta, ignore esta mensagem.')
                ->salutation('AL Commerce');
        });

        RateLimiter::for('verification-email', function (Request $request): Limit {
            return Limit::perMinute(6)->by((string) $request->user()->getAuthIdentifier())
                ->response(function (Request $request, array $headers): Response|JsonResponse {
                    $message = 'Muitos pedidos de verificação. Aguarde um minuto antes de reenviar.';

                    return $request->expectsJson()
                        ? response()->json(['message' => $message], 429, $headers)
                        : response()->view('auth.verify-email', ['resendError' => $message], 429, $headers);
                });
        });
    }
}
