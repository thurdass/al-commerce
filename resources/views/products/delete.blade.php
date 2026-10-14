@extends('layouts.app')

@section('title', 'Excluir produto')

@section('content')
    @include('partials.merchant-navigation')
    <div class="delete-panel">
        <div class="page-intro"><p class="eyebrow">Gerenciar produto</p><h1>Excluir produto</h1></div>
        <p>Você está prestes a excluir <strong>{{ $product->name }}</strong>.</p>
        <p class="muted">A exclusão é definitiva e também remove as imagens do produto. Para preservar os dados, você pode desativar o produto na listagem.</p>
        <form method="POST" action="{{ route('products.destroy', $product) }}">
            @csrf
            @method('DELETE')
            <label for="confirmed" class="check-field">
                <input id="confirmed" type="checkbox" name="confirmed" value="1" required>
                Confirmo a exclusão deste produto.
            </label>
            <div class="actions"><x-button variant="danger">Excluir definitivamente</x-button><a href="{{ route('products.index') }}">Cancelar</a></div>
        </form>
    </div>
@endsection
