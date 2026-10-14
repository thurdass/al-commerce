@extends('layouts.app')

@section('title', 'Criar loja')

@section('content')
    @include('partials.merchant-navigation', ['hasStore' => false])
    <div class="form-page">
        <div class="page-intro"><p class="eyebrow">Comece sua vitrine</p><h1>Criar minha loja</h1><p>Conte um pouco sobre seu negócio. Depois de criar a loja, você poderá adicionar uma imagem na edição.</p></div>
        <form method="POST" action="{{ route('stores.store') }}">
            @csrf
            @include('stores.partials.form', ['store' => null])
            <div class="actions"><x-button>Criar loja</x-button><a href="{{ route('dashboard') }}">Voltar</a></div>
        </form>
    </div>
@endsection
