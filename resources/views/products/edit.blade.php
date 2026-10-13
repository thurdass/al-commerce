@extends('layouts.app')

@section('title', 'Editar produto')

@section('content')
    <h1>Editar produto</h1>
    <p>Slug: <strong>{{ $product->slug }}</strong>. Ele será mantido ao alterar o nome.</p>
    <form method="POST" action="{{ route('products.update', $product) }}">
        @csrf
        @method('PUT')
        @include('products.partials.form', ['product' => $product])
        <div class="actions">
            <button type="submit">Salvar alterações</button>
            <a href="{{ route('products.index') }}">Voltar aos produtos</a>
        </div>
    </form>
@endsection
