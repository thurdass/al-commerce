@extends('layouts.app')

@section('title', 'Link de verificação indisponível')

@section('content')
    <section class="verification-page" aria-labelledby="verification-error-heading">
        <div class="page-intro">
            <p class="eyebrow">Sua conta / Verificação de e-mail</p>
            <h1 id="verification-error-heading">Este link não pode ser usado</h1>
            <p>O link é inválido, expirou ou pertence a outra conta.</p>
        </div>
        <p>Entre com a conta que recebeu a mensagem ou solicite um novo link de verificação.</p>
        <div class="actions">
            <x-button :href="route('verification.notice')">Voltar à verificação</x-button>
            <a href="{{ route('home') }}">Explorar lojas</a>
        </div>
        <div class="verification-account-actions">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="button-text">Entrar em outra conta</button>
            </form>
        </div>
    </section>
@endsection
