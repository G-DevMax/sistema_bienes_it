@extends('layouts.app')

@section('title', 'Listado de Tickets')

@section('content')
    @php
        $filtroTipo = request('tipo');
        $filtroEstado = request('estado');
        $etiquetasEstado = [
            \App\Models\Tickets\Ticket::ESTADO_PENDIENTE => 'Pendientes',
            \App\Models\Tickets\Ticket::ESTADO_ASIGNADO => 'Asignados',
            \App\Models\Tickets\Ticket::ESTADO_CERRADO => 'Cerrados',
        ];
    @endphp
    <div class="w-full max-w-7xl">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Listado de Tickets
                </h1>
                <p class="text-white/70 mt-1">Consulta, asigna y cierra las solicitudes registradas</p>
            </div>
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <!-- Barra de búsqueda por número de ticket o funcionario -->
        <div class="mb-4">
            <x-barra_de_busqueda placeholder="Buscar por número de ticket o funcionario..."></x-barra_de_busqueda>
        </div>

        <!-- Filtros por estado -->
        <div class="flex flex-nowrap sm:flex-wrap overflow-x-auto items-center gap-2 mb-3 pb-1">
            <a href="{{ route('tickets.index', array_filter(['tipo' => $filtroTipo, 'search' => request('search')])) }}"
                class="shrink-0 whitespace-nowrap px-4 py-1.5 rounded-full text-sm border transition-all {{ !$filtroEstado ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                Todos los estados
            </a>
            @foreach ($etiquetasEstado as $estado => $etiqueta)
                <a href="{{ route('tickets.index', array_filter(['estado' => $estado, 'tipo' => $filtroTipo, 'search' => request('search')])) }}"
                    class="shrink-0 whitespace-nowrap px-4 py-1.5 rounded-full text-sm border transition-all {{ $filtroEstado === $estado ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                    {{ $etiqueta }}
                </a>
            @endforeach
        </div>

        <!-- Filtros por tipo de problema -->
        <div class="flex flex-nowrap md:flex-wrap overflow-x-auto items-center gap-2 mb-4 pb-1">
            <a href="{{ route('tickets.index', array_filter(['estado' => $filtroEstado, 'search' => request('search')])) }}"
                class="shrink-0 whitespace-nowrap px-4 py-1.5 rounded-full text-sm border transition-all {{ !$filtroTipo ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                Todos
            </a>
            @foreach ($tiposProblema as $tipo)
                <a href="{{ route('tickets.index', array_filter(['tipo' => $tipo, 'estado' => $filtroEstado, 'search' => request('search')])) }}"
                    class="shrink-0 whitespace-nowrap px-4 py-1.5 rounded-full text-sm border transition-all {{ $filtroTipo === $tipo ? 'bg-white/15 border-white/30 text-white font-medium' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                    {{ $tipo }}
                </a>
            @endforeach
        </div>

        <!-- Tabla -->
        <div class="bg-slate-900/60 border border-white/10 rounded-2xl shadow-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-white">
                            <th class="hidden md:table-cell px-3 md:px-6 py-4 font-semibold">N° Ticket</th>
                            <th class="px-3 md:px-6 py-4 font-semibold">Funcionario</th>
                            <th class="hidden md:table-cell px-3 md:px-6 py-4 font-semibold">Tipo de Problema</th>
                            <th class="hidden md:table-cell px-3 md:px-6 py-4 font-semibold">Descripción</th>
                            <th class="px-3 md:px-6 py-4 font-semibold">Estado</th>
                            <th class="hidden md:table-cell px-3 md:px-6 py-4 font-semibold">Fecha de Creación</th>
                            <th class="px-3 md:px-6 py-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($tickets as $ticket)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="hidden md:table-cell px-3 md:px-6 py-4 font-mono font-medium text-white/70 whitespace-nowrap">
                                    {{ $ticket->numero_ticket }}
                                </td>
                                <td class="px-3 md:px-6 py-4 font-medium text-white">
                                    <div>{{ $ticket->funcionario->nombre ?? '—' }}</div>
                                    <div class="md:hidden mt-1.5 flex flex-wrap items-center gap-1.5 text-xs text-white/60">
                                        <span>#{{ $ticket->numero_ticket }} · {{ ($ticket->created_at ?? $ticket->fecha_creacion)?->format('d/m/Y H:i') }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($ticket->tipo_problema) }}">
                                            {{ $ticket->tipo_problema }}
                                        </span>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell px-3 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($ticket->tipo_problema) }}">
                                        {{ $ticket->tipo_problema }}
                                    </span>
                                </td>
                                <td class="hidden md:table-cell px-3 md:px-6 py-4 text-white/80 max-w-md truncate">
                                    {{ $ticket->descripcion_problema }}
                                </td>
                                <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeEstado($ticket->estado) }}">
                                        {{ $ticket->estado }}
                                    </span>
                                </td>
                                <td class="hidden md:table-cell px-3 md:px-6 py-4 whitespace-nowrap text-white/80">
                                    {{ ($ticket->created_at ?? $ticket->fecha_creacion)?->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-3 md:px-6 py-4 text-right">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                        class="inline-flex p-3 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                                        title="Ver detalle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 md:px-6 py-12 text-center text-white/50">
                                    No hay tickets registrados con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('layouts.pagination', ['paginator' => $tickets])
        </div>
    </div>
@endsection
