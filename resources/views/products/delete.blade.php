@extends('layouts.app')

@section('title', 'Excluir produto')

@section('content')
    <h1>Excluir produto</h1>
    <p>Você está prestes a excluir <strong>{{ $product->name }}</strong>.</p>
    <p>A exclusão é definitiva e também remove as imagens do produto. Para preservar os dados, você pode desativar o produto na listagem.</p>
    <form method="POST" action="{{ route('products.destroy', $product) }}">
        @csrf
        @method('DELETE')
        <label for="confirmed">
            <input id="confirmed" type="checkbox" name="confirmed" value="1" required>
            Confirmo a exclusão deste produto.
        </label>
        <div class="actions">
            <button type="submit">Excluir definitivamente</button>
            <a href="{{ route('products.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
