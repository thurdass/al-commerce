@if (! $withinStore)
    <div class="filter-field filter-field--city">
        <label for="cidade">{{ ($compact ?? false) ? 'Cidade' : 'Cidade (nome ou parte dele)' }}</label>
        <input id="cidade" @isset($formId) form="{{ $formId }}" @endisset name="cidade" @if ($compact ?? false) placeholder="{{ ($navigationFilters ?? false) ? 'Cidade' : 'Nome ou parte dele' }}" @endif maxlength="255" value="{{ $filters['cidade'] ?? '' }}">
    </div>

    <div class="filter-field filter-field--state">
        <label for="estado">Estado (UF)</label>
        <input id="estado" @isset($formId) form="{{ $formId }}" @endisset name="estado" maxlength="2" placeholder="{{ ($navigationFilters ?? false) ? 'UF' : 'BA' }}" value="{{ $filters['estado'] ?? '' }}">
    </div>
@endif

@if ($productSearch)
    <div class="filter-field">
        <label for="categoria">Categoria</label>
        <select id="categoria" @isset($formId) form="{{ $formId }}" @endisset name="categoria">
            <option value="">Todas as categorias</option>
            @foreach ($categories as $category)
                <option value="{{ $category->slug }}" @selected(($filters['categoria'] ?? '') === $category->slug)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="filter-field">
        <label for="disponibilidade">Disponibilidade</label>
        <select id="disponibilidade" @isset($formId) form="{{ $formId }}" @endisset name="disponibilidade">
            <option value="">Todas as disponibilidades</option>
            @foreach ($availabilityStatuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['disponibilidade'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
@endif

<div class="filter-field filter-field--sort">
    <label for="ordenar">Ordenar por</label>
    <select id="ordenar" @isset($formId) form="{{ $formId }}" @endisset name="ordenar">
        <option value="nome" @selected(($filters['ordenar'] ?? ($productSearch ? 'recentes' : 'nome')) === 'nome')>Nome (A–Z)</option>
        <option value="recentes" @selected(($filters['ordenar'] ?? ($productSearch ? 'recentes' : 'nome')) === 'recentes')>Mais recentes</option>
        @if ($productSearch)
            <option value="menor_preco" @selected(($filters['ordenar'] ?? '') === 'menor_preco')>Menor preço</option>
            <option value="maior_preco" @selected(($filters['ordenar'] ?? '') === 'maior_preco')>Maior preço</option>
        @endif
    </select>
</div>
