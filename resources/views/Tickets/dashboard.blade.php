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
            <button type="button" id="btn-alertas-tickets"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-medium bg-white/5 hover:bg-white/10 border border-white/15 text-white/80 transition-all cursor-pointer">
                <span id="indicador-alertas" class="w-2 h-2 rounded-full bg-[#FEDB39]"></span>
                <span id="texto-alertas">Activar notificaciones y sonido</span>
            </button>
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
                    <p id="contador-pendientes" class="text-4xl font-bold text-[#FEDB39] mt-2">{{ $pendientes }}</p>
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

        @php
            $mesActualServidor = now()->format('Y-m');
            $mesSeleccionado = $mes ?? $mesActualServidor;
            $nombreMesMostrar = $nombreMesSimple ?? ($nombreMes ?? 'Mes');
            $anioMostrar = $anio ?? substr($mesSeleccionado, 0, 4);
            $anioMinimoPicker = $anioMinimo ?? \App\Models\Tickets\Ticket::ANIO_MINIMO;
            $anioMaximoPicker = $anioMaximo ?? max(now()->year, (int) $anioMostrar, $anioMinimoPicker);
        @endphp

        <!-- Filtro por mes -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-3">
                <div class="relative inline-flex items-center">
                    <button type="button" id="btn-filtro-mes"
                        class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white/10 hover:bg-white/15 border border-white/20 text-white shadow-md transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Mes de {{ $nombreMesMostrar }}</span>
                        <span class="text-blue-300 text-xs font-medium bg-blue-500/20 border border-blue-400/30 px-2 py-0.5 rounded-md">
                            {{ $anioMostrar }}
                        </span>
                        <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <input type="month" id="input-filtro-mes" name="mes"
                        value="{{ $mesSeleccionado }}"
                        min="{{ $anioMinimoPicker }}-01"
                        max="{{ $anioMaximoPicker }}-12"
                        class="absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10"
                        title="Seleccionar mes para filtrar"
                        onclick="if (this.showPicker) { try { this.showPicker(); } catch (e) {} }"
                        onchange="if (this.value && (!this.min || this.value >= this.min) && (!this.max || this.value <= this.max)) { window.location.href = '{{ route('tickets.dashboard') }}?mes=' + this.value; }">
                </div>

                @if ($mesSeleccionado !== $mesActualServidor)
                    <a href="{{ route('tickets.dashboard') }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-blue-300 hover:text-white bg-blue-500/10 hover:bg-blue-500/20 border border-blue-400/20 transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Volver al mes actual</span>
                    </a>
                @endif
            </div>

            <p class="text-xs text-white/50">
                Filtrando estadísticas de <span class="text-white font-medium">{{ $nombreMesMostrar }} {{ $anioMostrar }}</span>
            </p>
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
                                    {{ $ticket->descripcion_problema }}
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ahora = new Date();
            const anioMinimo = {{ (int) $anioMinimoPicker }};
            const anioMaximo = Math.max(ahora.getFullYear(), {{ (int) $anioMaximoPicker }});
            const btnFiltroMes = document.getElementById('btn-filtro-mes');
            const inputFiltroMes = document.getElementById('input-filtro-mes');

            if (inputFiltroMes) {
                inputFiltroMes.max = `${anioMaximo}-12`;
            }

            // Sincronizar por defecto con el mes registrado en el dispositivo si no se indicó en la URL
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('mes') && ahora.getFullYear() >= anioMinimo) {
                const mesDispositivo = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}`;
                const mesServidor = '{{ $mesSeleccionado }}';
                if (mesDispositivo !== mesServidor) {
                    urlParams.set('mes', mesDispositivo);
                    window.location.replace(`${window.location.pathname}?${urlParams.toString()}`);
                    return;
                }
            }

            if (btnFiltroMes && inputFiltroMes) {
                btnFiltroMes.addEventListener('click', () => {
                    if (inputFiltroMes.showPicker) {
                        try {
                            inputFiltroMes.showPicker();
                        } catch (_) {}
                    }
                });
            }

            const urlEstado = @json(route('tickets.pendientes-status', ['mes' => $mesSeleccionado]));
            const contadorEl = document.getElementById('contador-pendientes');
            const btnAlertas = document.getElementById('btn-alertas-tickets');
            const textoAlertas = document.getElementById('texto-alertas');
            const indicadorAlertas = document.getElementById('indicador-alertas');

            let pendientesActual = {{ (int) $pendientes }};
            let ultimoId = {{ (int) ($ultimoPendienteId ?? 0) }};
            let audioCtx = null;

            function inicializarAudio() {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) {
                    return null;
                }
                if (!audioCtx) {
                    audioCtx = new AudioContextClass();
                }
                if (audioCtx.state === 'suspended') {
                    audioCtx.resume();
                }
                return audioCtx;
            }

            function reproducirSonido() {
                try {
                    const ctx = inicializarAudio();
                    if (!ctx) {
                        return;
                    }
                    [587.33, 880].forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        const inicio = ctx.currentTime + idx * 0.16;
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, inicio);
                        gain.gain.setValueAtTime(0.18, inicio);
                        gain.gain.exponentialRampToValueAtTime(0.001, inicio + 0.25);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(inicio);
                        osc.stop(inicio + 0.25);
                    });
                } catch (_) {}
            }

            function actualizarEstadoBoton() {
                if (!('Notification' in window)) {
                    textoAlertas.textContent = 'Sonido activo (sin soporte push)';
                    indicadorAlertas.className = 'w-2 h-2 rounded-full bg-blue-400';
                    return;
                }
                if (Notification.permission === 'granted') {
                    textoAlertas.textContent = 'Notificaciones y sonido activos';
                    indicadorAlertas.className = 'w-2 h-2 rounded-full bg-green-400';
                } else if (Notification.permission === 'denied') {
                    textoAlertas.textContent = 'Notificaciones bloqueadas (solo sonido)';
                    indicadorAlertas.className = 'w-2 h-2 rounded-full bg-red-400';
                }
            }

            async function activarAlertas() {
                inicializarAudio();
                if ('Notification' in window && Notification.permission === 'default') {
                    try {
                        await Notification.requestPermission();
                    } catch (_) {}
                }
                actualizarEstadoBoton();
            }

            btnAlertas?.addEventListener('click', () => {
                activarAlertas();
                reproducirSonido();
            });
            document.addEventListener('click', activarAlertas, { once: true });
            actualizarEstadoBoton();

            function notificarNuevoTicket(ticket) {
                reproducirSonido();
                if ('Notification' in window && Notification.permission === 'granted' && ticket) {
                    const notif = new Notification(`Nuevo Ticket Pendiente #${ticket.id}`, {
                        body: `${ticket.funcionario} (${ticket.tipo_problema}): ${ticket.descripcion_problema}`,
                        tag: `ticket-${ticket.id}`,
                    });
                    notif.onclick = () => {
                        window.focus();
                        notif.close();
                    };
                }
            }

            async function verificarPendientes() {
                try {
                    const res = await fetch(urlEstado, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    });
                    if (!res.ok) {
                        return;
                    }
                    const data = await res.json();
                    const nuevosPendientes = Number(data.pendientes ?? 0);
                    const nuevoUltimoId = Number(data.ultimo_id ?? 0);

                    document.title = `(${nuevosPendientes}) Panel de Tickets`;
                    if (contadorEl) {
                        contadorEl.textContent = String(nuevosPendientes);
                    }

                    if ((nuevoUltimoId > ultimoId && ultimoId !== 0) || nuevosPendientes > pendientesActual) {
                        notificarNuevoTicket(data.ultimo_ticket);
                    }

                    pendientesActual = nuevosPendientes;
                    if (nuevoUltimoId > 0) {
                        ultimoId = nuevoUltimoId;
                    }
                } catch (_) {}
            }

            setInterval(verificarPendientes, 10000);
        });
    </script>
@endsection
