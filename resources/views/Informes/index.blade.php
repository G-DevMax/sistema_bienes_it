@extends('layouts.app')

@section('title', 'Gestión de Informes')

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
        $filtroTipo = request('tipo');
    @endphp
    <div class="w-full max-w-7xl animate-fade-in-up">
        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Informes
                </h1>
                <p class="text-white/70 mt-1">Registra y consulta los informes de asistencia técnica</p>
            </div>
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <!-- Barra de búsqueda -->
        <div class="mb-4">
            <x-barra_de_busqueda placeholder="Buscar por funcionario o título..."></x-barra_de_busqueda>
        </div>

        <!-- Filtros por tipo de problema -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <a href="{{ route('informes.index', array_filter(['search' => request('search')])) }}"
                class="px-4 py-1.5 rounded-full text-sm border transition-all {{ !$filtroTipo ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                Todos
            </a>
            @foreach ($tiposProblema as $tipo)
                <a href="{{ route('informes.index', array_filter(['tipo' => $tipo, 'search' => request('search')])) }}"
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
                            <th class="px-6 py-4 font-semibold">Título</th>
                            <th class="px-6 py-4 font-semibold">Funcionario</th>
                            <th class="px-6 py-4 font-semibold">Tipo de Problema</th>
                            <th class="px-6 py-4 font-semibold">Fecha</th>
                            <th class="px-6 py-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($informes as $informe)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4 font-medium text-white/50">#{{ $informe->id }}</td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-white">{{ $informe->titulo }}</span>
                                    <span class="block text-xs text-white/50 mt-0.5">{{ $informe->detalleTexto() }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $informe->funcionario->nombre ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$informe->tipo_problema] ?? 'bg-white/10 text-white border-white/20' }}">
                                        {{ $informe->tipo_problema }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-white/80">
                                    {{ $informe->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('informes.show', $informe->id) }}"
                                            class="p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                                            title="Ver detalle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                        <a href="{{ route('informes.edit', $informe->id) }}"
                                            class="p-2 text-white/60 hover:text-blue-400 hover:bg-blue-400/10 rounded-lg transition-colors"
                                            title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-white/50">
                                    No hay informes registrados con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('layouts.pagination', ['paginator' => $informes])
        </div>
    </div>
@endsection
