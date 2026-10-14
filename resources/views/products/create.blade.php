@extends('layouts.app')

@section('title', 'Cadastrar produto')

@section('content')
    @include('partials.merchant-navigation')
    <div class="form-page">
        <div class="page-intro"><p class="eyebrow">Uma nova descoberta na sua loja</p><h1>Cadastrar produto</h1><p>Preencha os detalhes do produto. Após cadastrar, abra a edição para adicionar imagens.</p></div>
        <form method="POST" action="{{ route('products.store') }}">
            @csrf
            @include('products.partials.form', ['product' => null])
            <div class="actions"><x-button>Cadastrar produto</x-button><a href="{{ route('products.index') }}">Cancelar</a></div>
        </form>
    </div>
@endsection
