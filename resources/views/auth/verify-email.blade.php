@extends('layouts.app')

@section('title', 'Verifique seu e-mail')

@section('content')
    <section class="verification-page" aria-labelledby="verification-heading">
        <div class="page-intro">
            <p class="eyebrow">Sua conta / Falta só confirmar</p>
            <h1 id="verification-heading">Verifique seu e-mail</h1>
            <p>Um último passo para acessar seu painel e começar a cuidar da sua loja.</p>
        </div>
        @if (isset($resendError))
            <div class="alert alert-error" role="alert"><p>{{ $resendError }}</p></div>
        @endif
        <div class="verification-destination">
            <svg aria-hidden="true" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25"><rect x="2.5" y="4.5" width="19" height="15" rx="1"/><path d="m3 5 9 7 9-7"/></svg>
            <div><p>Endereço da sua conta</p><strong>{{ auth()->user()->email }}</strong></div>
        </div>
        <ol class="verification-steps">
            <li>Abra o e-mail de verificação enviado pelo AL Commerce.</li>
            <li>Use o link para confirmar seu endereço. Ele é válido por {{ config('auth.verification.expire') }} minutos.</li>
        </ol>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div class="actions">
                <x-button>{{ session('verification-link-sent') ? 'Reenviar novamente' : 'Reenviar e-mail de verificação' }}</x-button>
                <a href="{{ route('home') }}">Explorar lojas</a>
            </div>
        </form>
        <p class="verification-help">Não encontrou a mensagem? Confira também a pasta de spam. Se o link expirou ou você ainda não recebeu um e-mail, solicite outro pelo botão acima.</p>
        <div class="verification-account-actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="button-text">Entrar em outra conta</button>
            </form>
            <a href="{{ route('account.delete') }}">Excluir minha conta</a>
        </div>
    </section>
@endsection
