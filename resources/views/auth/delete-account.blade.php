@extends('layouts.app')

@section('title', 'Excluir minha conta')

@section('content')
    @if (auth()->user()->hasVerifiedEmail())
        @include('partials.merchant-navigation')
    @endif
    <div class="account-delete-page">
        <div class="page-intro">
            <p class="eyebrow">Sua conta / Encerramento</p>
            <h1>Excluir minha conta</h1>
            <p>Antes de continuar, confira o que será removido.</p>
            <p class="account-identity">{{ auth()->user()->email }}</p>
        </div>
        <section class="deletion-notice" aria-labelledby="deletion-warning-heading">
            <h2 id="deletion-warning-heading">Esta ação é permanente.</h2>
            <p>Sua conta, sua loja, todos os produtos e as imagens vinculadas serão excluídos. Não será possível recuperar esses dados.</p>
            <p>Ao concluir, você sairá da conta e voltará à página inicial.</p>
            @if ($hasStore && auth()->user()->hasVerifiedEmail())
                <p>Quer apenas pausar sua vitrine? Volte ao painel e desative a loja na edição. Desativar preserva seus dados.</p>
            @endif
        </section>
        <form method="POST" action="{{ route('account.destroy') }}" aria-labelledby="deletion-warning-heading">
            @csrf
            @method('DELETE')
            <x-input name="password" label="Senha atual" type="password" autocomplete="current-password" required />
            <x-input name="confirmation" label="Digite EXCLUIR para confirmar" hint="Use letras maiúsculas, exatamente como indicado." autocomplete="off" spellcheck="false" autocapitalize="characters" required />
            <div class="actions">
                <x-button variant="danger">Excluir minha conta definitivamente</x-button>
                @if (auth()->user()->hasVerifiedEmail())
                    <a href="{{ route('dashboard') }}">Cancelar e voltar ao painel</a>
                @else
                    <a href="{{ route('verification.notice') }}">Cancelar e voltar à verificação</a>
                @endif
            </div>
        </form>
    </div>
@endsection
