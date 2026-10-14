<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#171717">
    <title>@yield('title', 'Área do lojista') — AL Commerce</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fonts
</head>
<body class="@yield('body-class')">
    <a class="skip-link" href="#main-content">Ir para o conteúdo</a>
    @include('partials.header')
    <main id="main-content" class="container main-content" tabindex="-1">
        @include('partials.alerts')
        @yield('content')
    </main>
    <footer class="site-footer container">
        <a class="footer-brand" href="{{ route('home') }}">AL Commerce.</a>
        <p>Comércio local. Conexões reais.</p>
        <a href="{{ route('register') }}">Sua loja também pode estar aqui <span aria-hidden="true">↗</span></a>
    </footer>
</body>
</html>
