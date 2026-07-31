@if ($paginator->hasPages())
<nav aria-label="Navigasi halaman">
    <ul class="pagination pagination-sm mb-0 justify-content-end">
        {{-- Previous --}}
        <li class="page-item @if ($paginator->onFirstPage()) disabled @endif">
            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">
                &laquo;
            </a>
        </li>

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <li class="page-item @if ($page == $paginator->currentPage()) active @endif">
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    </li>
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        <li class="page-item @if (! $paginator->hasMorePages()) disabled @endif">
            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">
                &raquo;
            </a>
        </li>
    </ul>
</nav>
@endif