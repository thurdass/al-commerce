<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Área do lojista') — AL Commerce</title>
    <style>
        * { box-sizing: border-box; }
        body { max-width: 760px; margin: 0 auto; padding: 24px; font-family: system-ui, sans-serif; line-height: 1.5; color: #1f2937; background: #f8fafc; }
        header, nav { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        header { justify-content: space-between; margin-bottom: 32px; }
        main { background: white; border: 1px solid #d1d5db; padding: 24px; border-radius: 8px; }
        a { color: #1d4ed8; }
        label { display: block; margin-top: 16px; font-weight: 600; }
        input:not([type=checkbox]), textarea, select { display: block; width: 100%; padding: 10px; margin-top: 4px; font: inherit; border: 1px solid #6b7280; border-radius: 4px; }
        button { padding: 10px 16px; font: inherit; cursor: pointer; }
        .actions { margin-top: 24px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
        .errors { color: #991b1b; }
        .status { color: #166534; }
        .catalog-card { border-top: 1px solid #d1d5db; padding: 24px 0; }
        .catalog-image { max-width: 100%; height: auto; }
        .image-placeholder { padding: 32px; background: #e5e7eb; color: #374151; text-align: center; }
        dt { font-weight: 600; margin-top: 12px; }
        dd { margin-left: 0; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <header>
        <a href="{{ route('home') }}">AL Commerce</a>
        <nav aria-label="Navegação principal">
            @auth
                <a href="{{ route('dashboard') }}">Meu painel</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Sair</button>
                </form>
            @else
                <a href="{{ route('login') }}">Entrar</a>
                <a href="{{ route('register') }}">Criar conta</a>
            @endauth
        </nav>
    </header>
    <main>
        @if (session('status'))
            <p class="status" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div class="errors" role="alert">
                <p>Confira os dados informados:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
