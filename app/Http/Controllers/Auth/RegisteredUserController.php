<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        try {
            event(new Registered($user));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return to_route('verification.notice')->withErrors([
                'verification' => 'Sua conta foi criada, mas não conseguimos enviar o e-mail. Tente reenviar em instantes.',
            ]);
        }

        return to_route('verification.notice')->with('status', 'Enviamos um link de verificação para seu e-mail.');
    }
}
