@extends('layouts.app')

@section('title', 'Verifique seu e-mail')

@section('content')
    <div class="form-page">
        <div class="page-intro">
            <p class="eyebrow">AL Commerce / Sua conta</p>
            <h1>Verifique seu e-mail</h1>
            <p>Confirme seu endereço para acessar o painel e gerenciar sua loja.</p>
            <p>{{ auth()->user()->email }}</p>
        </div>
        @if (isset($resendError))
            <div class="alert alert-error" role="alert"><p>{{ $resendError }}</p></div>
        @endif
        <p>Abra o e-mail do AL Commerce e use o link de verificação. O link é válido por {{ config('auth.verification.expire') }} minutos.</p>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div class="actions">
                <x-button>{{ session('verification-link-sent') ? 'Reenviar novamente' : 'Reenviar e-mail de verificação' }}</x-button>
                <a href="{{ route('home') }}">Explorar lojas</a>
            </div>
        </form>
        <div class="actions"><a href="{{ route('account.delete') }}">Excluir minha conta</a></div>
    </div>
@endsection
