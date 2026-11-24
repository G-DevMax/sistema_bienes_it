@extends('layouts.app')

@section('title', 'Solución Común #' . $solucion->id)

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('detalles-soluciones.index') }}"
                    class="p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-white">Solución #{{ $solucion->id }}</h1>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($solucion->tipo_problema) }}">
                            {{ $solucion->tipo_problema }}
                        </span>
                    </div>
                    <p class="text-white/60 mt-1">
                        Registrada el {{ $solucion->created_at?->format('d/m/Y \a \l\a\s H:i') }}
                    </p>
                </div>
            </div>

            @can('detalles.gestionar')
                <a href="{{ route('detalles-soluciones.edit', $solucion->id) }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 text-white shadow-[0_0_15px_rgba(16,185,129,0.3)] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Editar Solución
                </a>
            @endcan
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <!-- Información General -->
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 md:p-8 shadow-lg space-y-6">
            <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Información General</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Tipo de Problema</span>
                    <span
                        class="inline-block px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($solucion->tipo_problema) }}">
                        {{ $solucion->tipo_problema }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Informes Vinculados</span>
                    <p class="font-medium text-white">{{ $solucion->informes_count ?? 0 }}</p>
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Tickets Vinculados</span>
                    <p class="font-medium text-white">{{ $solucion->tickets_count ?? 0 }}</p>
                </div>
            </div>

            <div>
                <span class="block text-xs font-medium text-white/50 mb-1">Solución Común</span>
                <div class="text-base text-white/95 bg-white/5 border border-white/10 p-4 rounded-xl">{{ $solucion->solucion }}</div>
            </div>

            @can('detalles.gestionar')
                <div class="pt-4 border-t border-white/10 flex justify-end">
                    @if (($solucion->informes_count ?? 0) > 0 || ($solucion->tickets_count ?? 0) > 0)
                        <p class="text-xs text-white/50">
                            No se puede eliminar esta solución porque está vinculada a informes o tickets.
                        </p>
                    @else
                        <form action="{{ route('detalles-soluciones.destroy', $solucion->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                onclick="return confirm('¿Eliminar esta solución común?')"
                                class="px-4 py-2 rounded-xl text-sm font-semibold border border-[#D61C4E]/40 text-[#ff5c86] hover:bg-[#D61C4E]/15 transition-colors">
                                Eliminar Solución
                            </button>
                        </form>
                    @endif
                </div>
            @endcan
        </div>
    </div>
@endsection
