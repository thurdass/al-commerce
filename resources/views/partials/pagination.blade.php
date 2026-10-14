@if ($paginator->hasPages())
    <nav class="actions" aria-label="Paginação de {{ $label }}">
        @if (! $paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif
        <span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} ({{ $paginator->total() }} {{ $label }})</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Próxima</a>
        @endif
    </nav>
@endif
