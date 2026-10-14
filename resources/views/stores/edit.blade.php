@extends('layouts.app')

@section('title', 'Editar loja')

@section('content')
    @include('partials.merchant-navigation')
    <div class="form-page">
        <div class="page-intro"><p class="eyebrow">Sua loja, do seu jeito</p><h1>Editar minha loja</h1><p>Atualize as informações que seus clientes encontram na vitrine.</p><p class="field-hint">Endereço da vitrine: <strong>{{ $store->slug }}</strong>. Ele será atualizado ao alterar o nome da loja.</p></div>
        <form method="POST" action="{{ route('stores.update', $store) }}">
            @csrf
            @method('PUT')
            @include('stores.partials.form', ['store' => $store])
            <div class="actions"><x-button>Salvar alterações</x-button><a href="{{ route('dashboard') }}">Voltar</a></div>
        </form>
        @include('stores.partials.image')
    </div>
@endsection
