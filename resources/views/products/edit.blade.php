@extends('layouts.app')

@section('title', 'Editar produto')

@section('content')
    @include('partials.merchant-navigation')
    <div class="form-page">
        <div class="page-intro"><p class="eyebrow">Mantenha sua vitrine atualizada</p><h1>Editar produto</h1><p>Revise os detalhes, a disponibilidade e as imagens do produto.</p><p class="field-hint">Endereço do produto: <strong>{{ $product->slug }}</strong>. Ele será mantido ao alterar o nome.</p></div>
        <form method="POST" action="{{ route('products.update', $product) }}">
            @csrf
            @method('PUT')
            @include('products.partials.form', ['product' => $product])
            <div class="actions"><x-button>Salvar alterações</x-button><a href="{{ route('products.index') }}">Voltar aos produtos</a></div>
        </form>
        @include('products.partials.images')
    </div>
@endsection
