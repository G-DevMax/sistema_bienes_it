{{-- Paginacion reutilizable. Uso: @include('layouts.pagination', ['paginator' => $coleccionPaginada]) --}}
<div class="px-6 py-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
    <span class="text-sm text-white/60">
        Mostrando <span class="text-white font-bold">{{ $paginator->firstItem() ?? 0 }}</span>
        a <span class="text-white font-bold">{{ $paginator->lastItem() ?? 0 }}</span>
        de <span class="text-white font-bold">{{ $paginator->total() }}</span> resultados
    </span>
    <div class="flex gap-1">
        @if ($paginator->onFirstPage())
            <span class="px-3 py-1 rounded-md bg-white/5 text-white/40 cursor-not-allowed text-sm">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1 rounded-md bg-white/10 text-white hover:bg-white/20 text-sm">Anterior</a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $pagina => $url)
            @if ($pagina == $paginator->currentPage())
                <span class="px-3 py-1 rounded-md bg-[#1CD6CE] text-[#293462] font-bold text-sm">{{ $pagina }}</span>
            @else
                <a href="{{ $url }}" class="px-3 py-1 rounded-md bg-white/10 text-white hover:bg-white/20 text-sm">{{ $pagina }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1 rounded-md bg-white/10 text-white hover:bg-white/20 text-sm">Siguiente</a>
        @else
            <span class="px-3 py-1 rounded-md bg-white/5 text-white/40 cursor-not-allowed text-sm">Siguiente</span>
        @endif
    </div>
</div>
