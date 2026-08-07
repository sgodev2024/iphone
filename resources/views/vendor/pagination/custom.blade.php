@if ($paginator->hasPages())
    <nav>
        <ul class="pagination justify-content-center gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled client-pagination-prev"><span class="page-link"><span
                            class="pagination-arrow-desktop">&laquo;</span><span
                            class="pagination-arrow-mobile">&lsaquo;</span></span></li>
            @else
                <li class="page-item client-pagination-prev"><a class="page-link"
                        href="{{ $paginator->previousPageUrl() }}" rel="prev"><span
                            class="pagination-arrow-desktop">&laquo;</span><span
                            class="pagination-arrow-mobile">&lsaquo;</span></a></li>
            @endif

            <li class="client-pagination-mobile-label">Trang {{ $paginator->currentPage() }} /
                {{ $paginator->lastPage() }}</li>

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled client-pagination-ellipsis"><span
                            class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active client-pagination-page"><span
                                    class="page-link">{{ $page }}</span></li>
                        @elseif (
                            $page == 1 ||
                                $page == $paginator->lastPage() ||
                                ($page >= $paginator->currentPage() - 1 && $page <= $paginator->currentPage() + 1))
                            <li class="page-item client-pagination-page"><a class="page-link"
                                    href="{{ $url }}">{{ $page }}</a></li>
                        @elseif ($page == 2 || $page == $paginator->lastPage() - 1)
                            <li class="page-item disabled client-pagination-ellipsis"><span
                                    class="page-link">...</span></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item client-pagination-next"><a class="page-link"
                        href="{{ $paginator->nextPageUrl() }}" rel="next"><span
                            class="pagination-arrow-desktop">&raquo;</span><span
                            class="pagination-arrow-mobile">&rsaquo;</span></a></li>
            @else
                <li class="page-item disabled client-pagination-next"><span class="page-link"><span
                            class="pagination-arrow-desktop">&raquo;</span><span
                            class="pagination-arrow-mobile">&rsaquo;</span></span></li>
            @endif
        </ul>
    </nav>
@endif
