@extends('layouts.app')

@section('title', 'Detalle de Problema #' . $detalle->id)

@section('content')
    @php
        $badgesTipo = [
            'Hardware' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            'Software' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
            'Red' => 'bg-[#1CD6CE]/20 text-[#1CD6CE] border-[#1CD6CE]/30',
            'Impresora' => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
            'Cuentas y Accesos' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            'Consulta' => 'bg-green-500/20 text-green-300 border-green-500/30',
        ];
    @endphp
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('detalles-problemas.index') }}"
                    class="p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-white">Detalle #{{ $detalle->id }}</h1>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$detalle->tipo_problema] ?? 'bg-white/10 text-white border-white/20' }}">
                            {{ $detalle->tipo_problema }}
                        </span>
                    </div>
                    <p class="text-white/60 mt-1">
                        Registrado el {{ $detalle->created_at?->format('d/m/Y \a \l\a\s H:i') }}
                    </p>
                </div>
            </div>

            <a href="{{ route('detalles-problemas.edit', $detalle->id) }}"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white shadow-[0_0_15px_rgba(59,130,246,0.3)] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
                Editar Detalle
            </a>
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
                        class="inline-block px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$detalle->tipo_problema] ?? 'bg-white/10 text-white border-white/20' }}">
                        {{ $detalle->tipo_problema }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Informes Vinculados</span>
                    <p class="font-medium text-white">{{ $detalle->informes_count ?? 0 }}</p>
                </div>
            </div>

            <div>
                <span class="block text-xs font-medium text-white/50 mb-1">Detalle del Problema</span>
                <div class="text-base text-white/95 bg-white/5 border border-white/10 p-4 rounded-xl">{{ $detalle->detalle }}</div>
            </div>

            <div class="pt-4 border-t border-white/10 flex justify-end">
                <form action="{{ route('detalles-problemas.destroy', $detalle->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        onclick="return confirm('¿Eliminar este detalle común? Los informes ya registrados conservarán sus datos como Otro (especificar).')"
                        class="px-4 py-2 rounded-xl text-sm font-semibold border border-[#D61C4E]/40 text-[#ff5c86] hover:bg-[#D61C4E]/15 transition-colors">
                        Eliminar Detalle
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
