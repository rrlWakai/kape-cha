@if ($paginator->hasPages())
    <nav class="kc-pager" role="navigation" aria-label="Pagination">
        <p>
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            of {{ $paginator->total() }}
        </p>

        <div class="kc-pager__pages">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-hidden="true">Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="is-disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="is-disabled" aria-hidden="true">Next</span>
            @endif
        </div>
    </nav>
@endif
