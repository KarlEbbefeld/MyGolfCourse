{{-- Seitennummerierung (eigene Vorlage – Laravel 13 bringt „simple-default“ nicht mehr mit). Nutzung: $items->links('partials.pagination') --}}
@if ($paginator->hasPages())
    <nav class="pager" aria-label="Seiten">
        @if ($paginator->onFirstPage())
            <span class="pager__link is-disabled" aria-disabled="true">← Zurück</span>
        @else
            <a class="pager__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">← Zurück</a>
        @endif

        @if (method_exists($paginator, 'lastPage'))
            <span class="pager__info num">Seite {{ $paginator->currentPage() }} von {{ $paginator->lastPage() }}</span>
        @endif

        @if ($paginator->hasMorePages())
            <a class="pager__link" href="{{ $paginator->nextPageUrl() }}" rel="next">Weiter →</a>
        @else
            <span class="pager__link is-disabled" aria-disabled="true">Weiter →</span>
        @endif
    </nav>
@endif
