@extends('layouts.app')

@section('title', 'Detalle del Informe #' . $informe->id)

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
    <div class="w-full max-w-4xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('informes.index') }}"
                    class="p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                        </path>
                    </svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-white">Informe #{{ $informe->id }}</h1>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$informe->tipo_problema] ?? 'bg-white/10 text-white border-white/20' }}">
                            {{ $informe->tipo_problema }}
                        </span>
                    </div>
                    <p class="text-white/60 mt-1">
                        Registrado el {{ $informe->created_at?->format('d/m/Y \a \l\a\s H:i') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('informes.pdf', $informe->id) }}" target="_blank"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border border-[#1CD6CE]/40 text-[#1CD6CE] hover:bg-[#1CD6CE]/10 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                        </path>
                    </svg>
                    Imprimir Informe
                </a>
                <a href="{{ route('informes.edit', $informe->id) }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white shadow-[0_0_15px_rgba(59,130,246,0.3)] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Editar Informe
                </a>
            </div>
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <!-- Información General -->
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-6 md:p-8 shadow-lg space-y-6">
            <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Información General</h2>

            <div>
                <span class="block text-xs font-medium text-white/50 mb-1">Título</span>
                <p class="text-lg font-semibold text-white">{{ $informe->titulo }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Funcionario Atendido</span>
                    <p class="font-medium text-white">{{ $informe->funcionario->nombre ?? '—' }}</p>
                    @if ($informe->funcionario?->dependencia)
                        <span class="block text-xs text-white/50 mt-0.5">{{ $informe->funcionario->dependencia->nombre }}</span>
                    @endif
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Tipo de Problema</span>
                    <span
                        class="inline-block px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$informe->tipo_problema] ?? 'bg-white/10 text-white border-white/20' }}">
                        {{ $informe->tipo_problema }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Detalle del Problema</span>
                    <p class="font-medium text-white">{{ $informe->detalleTexto() }}</p>
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Fecha y Hora de Registro</span>
                    <p class="font-medium text-white">{{ $informe->created_at?->format('d/m/Y H:i') }}</p>
                    @if ($informe->updated_at && $informe->updated_at->ne($informe->created_at))
                        <span class="block text-xs text-white/40 mt-0.5">
                            Última edición: {{ $informe->updated_at->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="pt-4 border-t border-white/10">
                <span class="block text-xs font-medium text-white/50 mb-2">Observaciones / Solución</span>
                @if ($informe->observacion)
                    <div class="text-sm text-white/90 bg-white/5 border border-white/10 p-4 rounded-xl whitespace-pre-line">{{ $informe->observacion }}</div>
                @else
                    <p class="text-sm text-white/40 italic">Sin observaciones registradas.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
