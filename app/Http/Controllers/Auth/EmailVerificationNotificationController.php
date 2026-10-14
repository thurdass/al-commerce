<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailVerificationNotificationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return to_route('verification.notice')->withErrors([
                'verification' => 'Não conseguimos enviar o e-mail agora. Tente novamente em instantes.',
            ]);
        }

        return to_route('verification.notice')
            ->with('status', 'Enviamos um novo link de verificação. Confira sua caixa de entrada e a pasta de spam.')
            ->with('verification-link-sent', true);
    }
}
