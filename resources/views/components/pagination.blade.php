{{--
    Composant Pagination Premium
    
    Usage:
    <x-pagination :paginator="$products" />
    
    Props:
    - paginator: LengthAwarePaginator (required)
--}}

@props(['paginator'])

@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
    <div class="flex justify-between flex-1 sm:hidden">
        {{-- Previous (Mobile) --}}
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                Précédent
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-accent focus:border-accent active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                Précédent
            </a>
        @endif

        {{-- Next (Mobile) --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-accent focus:border-accent active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                Suivant
            </a>
        @else
            <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                Suivant
            </span>
        @endif
    </div>

    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-gray-700 leading-5">
                Affichage de
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                à
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                sur
                <span class="font-medium">{{ $paginator->total() }}</span>
                résultats
            </p>
        </div>

        <div>
            <span class="relative z-0 inline-flex shadow-sm rounded-lg">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Précédent">
                        <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-l-lg leading-5" aria-hidden="true">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-lg leading-5 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring ring-accent focus:border-accent active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150" aria-label="Précédent">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($paginator->links()->elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5">{{ $element }}</span>
                        </span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-accent border border-accent cursor-default leading-5">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring ring-accent focus:border-accent active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150" aria-label="Aller à la page {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-lg leading-5 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring ring-accent focus:border-accent active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150" aria-label="Suivant">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Suivant">
                        <span class="relative inline-flex items-center px-3 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default rounded-r-lg leading-5" aria-hidden="true">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </div>
</nav>
@endif
