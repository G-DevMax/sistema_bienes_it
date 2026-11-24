@extends('layouts.app')

@section('title', 'Soluciones Comunes')

@section('content')
    @php
        $filtroTipo = request('tipo');
    @endphp
    <div class="w-full max-w-7xl animate-fade-in-up">
        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-400 to-cyan-300 bg-clip-text text-transparent">
                    Soluciones Comunes
                </h1>
                <p class="text-white/70 mt-1">Administra las soluciones frecuentes por tipo de problema para el cierre de tickets e informes</p>
            </div>
            @can('detalles.gestionar')
                <a href="{{ route('detalles-soluciones.create') }}"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_25px_rgba(16,185,129,0.5)] transition-all gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Crear nueva solución
                </a>
            @endcan
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <!-- Barra de búsqueda -->
        <div class="mb-4">
            <x-barra_de_busqueda placeholder="Buscar por solución o tipo de problema..."></x-barra_de_busqueda>
        </div>

        <!-- Filtros por tipo de problema -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <a href="{{ route('detalles-soluciones.index', array_filter(['search' => request('search')])) }}"
                class="px-4 py-1.5 rounded-full text-sm border transition-all {{ !$filtroTipo ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                Todos
            </a>
            @foreach ($tiposProblema as $tipo)
                <a href="{{ route('detalles-soluciones.index', array_filter(['tipo' => $tipo, 'search' => request('search')])) }}"
                    class="px-4 py-1.5 rounded-full text-sm border transition-all {{ $filtroTipo === $tipo ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                    {{ $tipo }}
                </a>
            @endforeach
        </div>

        <!-- Tabla -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.3)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-[#1CD6CE]">
                            <th class="px-6 py-4 font-semibold">ID</th>
                            <th class="px-6 py-4 font-semibold">Tipo de Problema</th>
                            <th class="px-6 py-4 font-semibold">Solución Común</th>
                            <th class="px-6 py-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($soluciones as $solucion)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4 font-medium text-white/50">#{{ $solucion->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($solucion->tipo_problema) }}">
                                        {{ $solucion->tipo_problema }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-white">{{ $solucion->solucion }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('detalles-soluciones.show', $solucion->id) }}"
                                            class="p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                                            title="Ver solución">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                        @can('detalles.gestionar')
                                            <a href="{{ route('detalles-soluciones.edit', $solucion->id) }}"
                                                class="p-2 text-white/60 hover:text-blue-400 hover:bg-blue-400/10 rounded-lg transition-colors"
                                                title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-white/50">
                                    No hay soluciones comunes registradas con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('layouts.pagination', ['paginator' => $soluciones])
        </div>
    </div>
@endsection
