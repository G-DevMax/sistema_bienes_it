@extends('layouts.app')

@section('title', 'Estadísticas de Tickets')

@section('content')
    @php
        $mesActualServidor = now()->format('Y-m');
        $mesSeleccionado = $mes ?? $mesActualServidor;
        $nombreMesMostrar = $nombreMesSimple ?? ($nombreMes ?? 'Mes');
        $anioMostrar = $anio ?? substr($mesSeleccionado, 0, 4);
        $anioMinimoPicker = $anioMinimo ?? \App\Models\Tickets\Ticket::ANIO_MINIMO;
        $anioMaximoPicker = $anioMaximo ?? max(now()->year, (int) $anioMostrar, $anioMinimoPicker);
        $esVistaUsuarios = $vista === 'usuarios';
        $datosGrafico = [
            'labels' => array_column($filasTabla, 'label'),
            'valores' => array_column($filasTabla, 'total'),
            'colores' => $colores,
            'total_mes' => $totalMes,
        ];
    @endphp

    <div class="w-full max-w-7xl">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Estadísticas de Tickets
                </h1>
                <p class="text-white/70 mt-1">
                    Distribución mensual correspondiente a {{ $nombreMes }}
                </p>
            </div>
        </div>

        <!-- Barra de filtros -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="relative inline-flex items-center">
                    <button type="button" id="btn-filtro-mes"
                        class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white/10 hover:bg-white/15 border border-white/20 text-white shadow-md transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <span>Mes de {{ $nombreMesMostrar }}</span>
                        <span
                            class="text-blue-300 text-xs font-medium bg-blue-500/20 border border-blue-400/30 px-2 py-0.5 rounded-md">
                            {{ $anioMostrar }}
                        </span>
                        <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <input type="month" id="input-filtro-mes" name="mes" value="{{ $mesSeleccionado }}"
                        min="{{ $anioMinimoPicker }}-01" max="{{ $anioMaximoPicker }}-12"
                        data-anio-minimo="{{ (int) $anioMinimoPicker }}"
                        data-anio-maximo="{{ (int) $anioMaximoPicker }}"
                        data-mes-servidor="{{ $mesSeleccionado }}"
                        data-base-url="{{ route('tickets.estadisticas', ['vista' => $vista]) }}"
                        class="absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10"
                        title="Seleccionar mes para filtrar">
                </div>

                @if ($mesSeleccionado !== $mesActualServidor)
                    <a href="{{ route('tickets.estadisticas', ['vista' => $vista]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-blue-300 hover:text-white bg-blue-500/10 hover:bg-blue-500/20 border border-blue-400/20 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        <span>Volver al mes actual</span>
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tickets.estadisticas', ['mes' => $mesSeleccionado, 'vista' => 'problemas']) }}"
                    class="px-4 py-2 rounded-xl text-sm border transition-all {{ !$esVistaUsuarios ? 'bg-blue-500/25 border-blue-400/50 text-white font-semibold shadow-md' : 'bg-white/5 border-white/10 text-white/70 hover:text-white hover:bg-white/10' }}">
                    Tipos de Problema
                </a>
                <a href="{{ route('tickets.estadisticas', ['mes' => $mesSeleccionado, 'vista' => 'usuarios']) }}"
                    class="px-4 py-2 rounded-xl text-sm border transition-all {{ $esVistaUsuarios ? 'bg-[#1CD6CE]/20 border-[#1CD6CE]/50 text-white font-semibold shadow-md' : 'bg-white/5 border-white/10 text-white/60 hover:text-white hover:bg-white/10' }}">
                    Usuarios
                </a>
            </div>
        </div>

        <!-- Gráfico de barras horizontales (Chart.js) -->
        <div
            class="bg-slate-900/60 border border-white/10 rounded-2xl shadow-xl p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <h2 class="text-lg font-bold text-white">
                    {{ $esVistaUsuarios ? 'Usuarios que más tickets abrieron' : 'Problemas más frecuentes del mes' }}
                </h2>
                <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white/10 border border-white/15 text-white/80">
                    Total del mes: {{ $totalMes }}
                </span>
            </div>

            <div id="contenedor-grafico" class="relative h-80 w-full {{ $totalMes === 0 ? 'hidden' : '' }}">
                <canvas id="grafico-estadisticas"
                    data-chart-type="bar"
                    data-chart-data='@json($datosGrafico)'
                    data-chart-label="Tickets"
                    aria-label="{{ $esVistaUsuarios ? 'Gráfico de barras horizontales de los 8 usuarios con más tickets del mes' : 'Gráfico de barras horizontales de tipos de problema del mes' }}"
                    role="img"></canvas>
            </div>

            <p id="mensaje-sin-datos" class="text-center text-sm text-white/50 py-10 {{ $totalMes > 0 ? 'hidden' : '' }}">
                No hay tickets registrados en este mes.
            </p>
        </div>

        <!-- Tabla de totales ordenada de mayor a menor -->
        <div
            class="bg-slate-900/60 border border-white/10 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                <h2 class="text-lg font-bold text-white">
                    {{ $esVistaUsuarios ? 'Detalle de los 8 usuarios con más solicitudes' : 'Cantidad total por tipo de problema' }}
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-white">
                            <th class="px-6 py-4 font-semibold w-16">#</th>
                            <th class="px-6 py-4 font-semibold">
                                {{ $esVistaUsuarios ? 'Usuario' : 'Tipo de Problema' }}
                            </th>
                            <th class="px-6 py-4 font-semibold text-right">Cantidad</th>
                            <th class="px-6 py-4 font-semibold text-right w-48">% del mes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($filasTabla as $indice => $fila)
                            @php
                                $porcentaje = $totalMes > 0 ? round(($fila['total'] / $totalMes) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-medium text-white/50">{{ $indice + 1 }}</td>
                                <td class="px-6 py-4">
                                    @if ($esVistaUsuarios)
                                        <span class="font-medium text-white">{{ $fila['label'] }}</span>
                                    @else
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeTipo($fila['label']) }}">
                                            {{ $fila['label'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-white">
                                    {{ $fila['total'] }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <div class="w-24 h-2 rounded-full bg-white/10 overflow-hidden">
                                            <div class="h-full rounded-full"
                                                style="width: {{ min(100, $porcentaje) }}%; background-color: {{ $colores[$indice] ?? '#60a5fa' }};">
                                            </div>
                                        </div>
                                        <span class="text-white/75 w-12 text-right">{{ $porcentaje }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-white/50">
                                    No hay tickets registrados en este mes.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @vite('resources/js/estadisticas-charts.js')
@endsection
