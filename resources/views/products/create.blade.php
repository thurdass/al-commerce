@extends('layouts.app')

@section('title', 'Cadastrar produto')

@section('content')
    <h1>Cadastrar produto</h1>
    <p>O slug será gerado automaticamente e permanecerá o mesmo após a criação.</p>
    <p>Após cadastrar, abra a edição do produto para adicionar imagens.</p>
    <form method="POST" action="{{ route('products.store') }}">
        @csrf
        @include('products.partials.form', ['product' => null])
        <div class="actions">
            <button type="submit">Cadastrar produto</button>
            <a href="{{ route('products.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
