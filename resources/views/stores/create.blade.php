@extends('layouts.app')

@section('title', 'Criar loja')

@section('content')
    <h1>Criar minha loja</h1>
    <p>O slug será gerado automaticamente a partir do nome da loja.</p>
    <form method="POST" action="{{ route('stores.store') }}">
        @csrf
        @include('stores.partials.form', ['store' => null])
        <div class="actions">
            <button type="submit">Criar loja</button>
            <a href="{{ route('dashboard') }}">Voltar</a>
        </div>
    </form>
@endsection
