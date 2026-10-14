<form method="GET" action="{{ $searchUrl }}" role="search" aria-label="Buscar lojas">
    <label for="q">Nome da loja</label>
    <input id="q" name="q" type="search" maxlength="255" value="{{ $filters['q'] ?? '' }}">

    <label for="cidade">Cidade (nome ou parte dele)</label>
    <input id="cidade" name="cidade" maxlength="255" value="{{ $filters['cidade'] ?? '' }}">

    <label for="estado">Estado (UF)</label>
    <input id="estado" name="estado" maxlength="2" placeholder="BA" value="{{ $filters['estado'] ?? '' }}">

    <label for="ordenar">Ordenar por</label>
    <select id="ordenar" name="ordenar">
        <option value="nome" @selected(($filters['ordenar'] ?? 'nome') === 'nome')>Nome (A–Z)</option>
        <option value="recentes" @selected(($filters['ordenar'] ?? '') === 'recentes')>Mais recentes</option>
    </select>

    <div class="actions">
        <button type="submit">Buscar</button>
        <a href="{{ $searchUrl }}">Limpar filtros</a>
    </div>
</form>
