@extends('layouts.app')

@section('title', 'Panel de Tickets')

@section('content')
    <div class="w-full max-w-7xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Panel de Tickets
                </h1>
                <p class="text-white/70 mt-1">Resumen de solicitudes de soporte técnico</p>
            </div>
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <!-- Tarjetas de estado -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div
                class="bg-white/5 backdrop-blur-xl border border-[#FEDB39]/30 rounded-2xl p-6 shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-white/60">Tickets pendientes</p>
                    <p class="text-4xl font-bold text-[#FEDB39] mt-2">{{ $pendientes }}</p>
                </div>
                <div class="p-3.5 rounded-xl bg-[#FEDB39]/15 text-[#FEDB39]">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>

            <div
                class="bg-white/5 backdrop-blur-xl border border-blue-400/30 rounded-2xl p-6 shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-white/60">Tickets Asignados</p>
                    <p class="text-4xl font-bold text-blue-300 mt-2">{{ $asignados }}</p>
                </div>
                <div class="p-3.5 rounded-xl bg-blue-500/15 text-blue-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>

            <div
                class="bg-white/5 backdrop-blur-xl border border-green-400/30 rounded-2xl p-6 shadow-lg flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-white/60">Tickets Cerrados</p>
                    <p class="text-4xl font-bold text-green-300 mt-2">{{ $cerrados }}</p>
                </div>
                <div class="p-3.5 rounded-xl bg-green-500/15 text-green-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Tickets recientes -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.3)] overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="text-lg font-bold text-white">Tickets recientes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-white">
                            <th class="px-6 py-4 font-semibold">Funcionario</th>
                            <th class="px-6 py-4 font-semibold">Tipo de Problema</th>
                            <th class="px-6 py-4 font-semibold">Descripción</th>
                            <th class="px-6 py-4 font-semibold">Estado</th>
                            <th class="px-6 py-4 font-semibold text-right">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($ticketsRecientes as $ticket)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-medium text-white">
                                    {{ $ticket->funcionario->nombre ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($ticket->tipo_problema) }}">
                                        {{ $ticket->tipo_problema }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-white/80 max-w-md truncate">
                                    {{ $ticket->descripcion_problea }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeEstado($ticket->estado) }}">
                                        {{ $ticket->estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                        class="inline-flex p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
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
                                <td colspan="5" class="px-6 py-12 text-center text-white/50">
                                    Aún no hay tickets registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
