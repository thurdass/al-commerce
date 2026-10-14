<form method="GET" action="{{ $searchUrl }}" role="search" aria-label="{{ $productSearch ? 'Buscar produtos' : 'Buscar lojas' }}">
    @if ($productSearch && ! $withinStore)
        <input type="hidden" name="tipo" value="produtos">
    @endif
    <label for="q">{{ $productSearch ? 'Nome do produto' : 'Nome da loja' }}</label>
    <input id="q" name="q" type="search" maxlength="255" value="{{ $filters['q'] ?? '' }}">

    @if (! $withinStore)
        <label for="cidade">Cidade (nome ou parte dele)</label>
        <input id="cidade" name="cidade" maxlength="255" value="{{ $filters['cidade'] ?? '' }}">

        <label for="estado">Estado (UF)</label>
        <input id="estado" name="estado" maxlength="2" placeholder="BA" value="{{ $filters['estado'] ?? '' }}">
    @endif

    @if ($productSearch)
        <label for="categoria">Categoria</label>
        <select id="categoria" name="categoria">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected(($filters['categoria'] ?? '') === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>

        <label for="disponibilidade">Disponibilidade</label>
        <select id="disponibilidade" name="disponibilidade">
            <option value="">Todas as disponibilidades</option>
            @foreach ($availabilityStatuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['disponibilidade'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    @endif

    <label for="ordenar">Ordenar por</label>
    <select id="ordenar" name="ordenar">
        <option value="nome" @selected(($filters['ordenar'] ?? ($productSearch ? 'recentes' : 'nome')) === 'nome')>Nome (A–Z)</option>
        <option value="recentes" @selected(($filters['ordenar'] ?? ($productSearch ? 'recentes' : 'nome')) === 'recentes')>Mais recentes</option>
        @if ($productSearch)
            <option value="menor_preco" @selected(($filters['ordenar'] ?? '') === 'menor_preco')>Menor preço</option>
            <option value="maior_preco" @selected(($filters['ordenar'] ?? '') === 'maior_preco')>Maior preço</option>
        @endif
    </select>

    <div class="actions">
        <button type="submit">Buscar</button>
        <a href="{{ $productSearch && ! $withinStore ? route('home', ['tipo' => 'produtos']) : $searchUrl }}">Limpar filtros</a>
    </div>
</form>
