<header class="site-header">
    <div class="header-inner container">
        <a class="brand" href="{{ route('home') }}" aria-label="AL Commerce — início"><img src="{{ asset('al-commerce-logo.svg') }}" alt="AL Commerce" width="1254" height="1254"></a>
        @hasSection('header-search')
            @yield('header-search')
        @else
            <nav class="catalog-navigation" aria-label="Tipo de busca">
                <a href="{{ route('home') }}">Lojas</a>
                <a href="{{ route('home', ['tipo' => 'produtos']) }}">Produtos</a>
            </nav>
            <form class="header-search" method="GET" action="{{ route('home') }}" role="search" aria-label="Buscar lojas">
                <label class="visually-hidden" for="header-q">Nome da loja</label>
                <input id="header-q" name="q" type="search" maxlength="255" placeholder="Encontre uma loja">
                <button class="search-button" type="submit" aria-label="Buscar lojas"><svg aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg></button>
            </form>
        @endif
        <nav class="account-navigation" aria-label="Navegação principal">
            @auth
                <a href="{{ route('dashboard') }}">Meu painel</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button-text" type="submit">Sair</button>
                </form>
            @else
                <a href="{{ route('login') }}">Entrar</a>
                <a class="register-link" href="{{ route('register') }}">Criar conta</a>
            @endauth
        </nav>
    </div>
</header>
