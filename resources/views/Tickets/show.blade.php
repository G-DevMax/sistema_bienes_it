@extends('layouts.app')

@section('title', 'Detalle del Ticket #' . $ticket->numero_ticket)

@section('content')
    <div class="w-full max-w-5xl">
        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('tickets.index') }}"
                    class="p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all"
                    title="Volver al listado">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">Ticket #{{ $ticket->numero_ticket }}</h1>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium border {{ \App\Models\Tickets\Ticket::badgeEstado($ticket->estado) }}">
                            {{ $ticket->estado }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            @include('layouts.alerts')
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Información del Ticket -->
            <div class="md:col-span-2 bg-slate-900/60 border border-white/10 rounded-2xl p-6 shadow-xl space-y-6">
                <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Información General</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <span class="block text-xs font-medium text-white/50 mb-1">Funcionario Solicitante</span>
                        <p class="font-medium text-white">{{ $ticket->funcionario->nombre ?? '—' }}</p>
                        @if ($ticket->funcionario?->dependencia)
                            <span
                                class="block text-xs text-white/50 mt-0.5">{{ $ticket->funcionario->dependencia->nombre }}</span>
                        @endif
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-white/50 mb-1">Técnico Asignado</span>
                        <p class="font-medium text-white">{{ $ticket->tecnicoAsignado->name ?? 'Sin asignar' }}</p>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-white/50 mb-1">Fecha y Hora de Creación</span>
                        <p class="font-medium text-white">
                            {{ ($ticket->created_at ?? $ticket->fecha_creacion)?->format('d/m/Y H:i') }}</p>
                    </div>

                    @if ($ticket->fecha_cierre)
                        <div>
                            <span class="block text-xs font-medium text-white/50 mb-1">Fecha y Hora de Cierre</span>
                            <p class="font-medium text-white">
                                {{ ($ticket->updated_at ?? $ticket->fecha_cierre)->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>

                <div>
                    <span class="block text-xs font-medium text-white/50 mb-1">Tipo de Problema</span>
                    <p class="font-medium text-white">{{ $ticket->tipo_problema }}</p>
                </div>

                <div class="pt-4 border-t border-white/10">
                    <span class="block text-xs font-medium text-white/50 mb-2">Descripción del Problema</span>
                    <div
                        class="text-sm text-white/90 bg-white/5 border border-white/10 p-4 rounded-xl whitespace-pre-line">
                        {{ $ticket->descripcion_problema }}</div>
                </div>

                @if ($ticket->imagen_problema)
                    @php $rutaImagen = '/' . ltrim($ticket->imagen_problema, '/'); @endphp
                    <div class="pt-4 border-t border-white/10">
                        <span class="block text-xs font-medium text-white/50 mb-2">Foto del Problema</span>
                        <a href="{{ $rutaImagen }}" target="_blank" rel="noopener noreferrer" class="inline-block max-w-full">
                            <img src="{{ $rutaImagen }}" alt="Foto del problema del ticket #{{ $ticket->numero_ticket }}"
                                loading="lazy" class="max-w-full max-h-64 rounded-lg cursor-pointer">
                        </a>
                    </div>
                @endif

                @if ($ticket->estaCerrado())
                    <div class="pt-4 border-t border-white/10 space-y-4">
                        <div>
                            <span class="block text-xs font-medium text-white/50 mb-1">Solución Común</span>
                            <p class="font-medium text-white">{{ $ticket->solucionTexto() }}</p>
                        </div>
                        <div>
                            <span class="block text-xs font-medium text-white/50 mb-2">Observación de Cierre</span>
                            @if ($ticket->observacion)
                                <div
                                    class="text-sm text-white/90 bg-white/5 border border-white/10 p-4 rounded-xl whitespace-pre-line">
                                    {{ $ticket->observacion }}</div>
                            @else
                                <p class="text-sm text-white/40 italic">Sin observaciones registradas al cierre.</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Panel de Acciones -->
            <div class="md:col-start-3 md:row-start-1 md:row-span-2">
                <div
                    class="bg-slate-900/60 border border-[#1CD6CE]/30 rounded-2xl p-6 shadow-xl md:sticky md:top-24 space-y-5">
                    <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3">Gestión del Ticket</h2>

                    @if ($ticket->estaCerrado())
                        <div
                            class="p-4 rounded-xl bg-green-500/15 border border-green-500/30 text-green-300 text-sm space-y-1">
                            <p class="font-bold flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Ticket Cerrado
                            </p>
                            <p class="text-xs text-green-200/80">
                                Atendido por {{ $ticket->tecnicoAsignado->name ?? 'Técnico' }} el
                                {{ ($ticket->updated_at ?? $ticket->fecha_cierre)?->format('d/m/Y H:i') }}.
                            </p>
                        </div>
                        @if ($ticket->informe)
                            <div class="mb-6">
                                <a href="{{ route('informes.show', $ticket->informe) }}"
                                    class="text-white/70 hover:text-white/100 font-medium text-xs">Ir a informes para
                                    imprimir
                                    el ticket</a>
                            </div>
                        @endif
                    @elseif ($ticket->estado === \App\Models\Tickets\Ticket::ESTADO_PENDIENTE)
                        @can('tickets.operar', $ticket)
                            <form action="{{ route('tickets.asignar', $ticket) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="asignado_a" class="block text-sm font-medium text-white/90 mb-2">
                                        Asignar Técnico
                                    </label>
                                    <select name="asignado_a" id="asignado_a" required
                                        class="searchable-select w-full px-4 py-2.5 bg-white/5 border border-white/20 rounded-xl text-base text-white focus:border-blue-400 outline-none">
                                        <option value="">Seleccione un técnico...</option>
                                        @foreach ($tecnicos as $tecnico)
                                            <option value="{{ $tecnico->id }}" @selected(old('asignado_a', auth()->id()) == $tecnico->id)>
                                                {{ $tecnico->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit"
                                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold shadow-lg shadow-blue-500/20 transition-all">
                                    Asignar Técnico
                                </button>
                            </form>
                        @else
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10 text-white/70 text-xs">
                                Solo lectura: solo un técnico autorizado o administrador puede asignar este ticket.
                            </div>
                        @endcan
                    @else
                        @can('tickets.operar', $ticket)
                            <!-- Ticket Asignado: permite cerrar con una solución común y opcionalmente reasignar -->
                            @php
                                $solucionesDisponibles = $solucionesComunes ?? collect();
                                $solucionInicial = old('solucion_id', $solucionesDisponibles->isEmpty() ? 'otro' : '');
                            @endphp
                            <form action="{{ route('tickets.cerrar', $ticket) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="solucion_id" class="block text-sm font-medium text-white/90 mb-2">
                                        Solución Común ({{ $ticket->tipo_problema }})
                                    </label>
                                    <select name="solucion_id" id="solucion_id" data-sort="none"
                                        placeholder="Selecciona o busca una solución..."
                                        class="searchable-select w-full px-4 py-2.5 bg-white/5 border border-white/20 rounded-xl text-base text-white focus:border-blue-400 outline-none">
                                        @if ($solucionesDisponibles->isNotEmpty())
                                            <option value="" @selected($solucionInicial === '')>Selecciona o busca una
                                                solución...
                                            </option>
                                        @endif
                                        @foreach ($solucionesDisponibles as $solucion)
                                            <option value="{{ $solucion->id }}" @selected((string) $solucionInicial === (string) $solucion->id)>
                                                {{ $solucion->solucion }}
                                            </option>
                                        @endforeach
                                        <option value="otro" @selected($solucionInicial === 'otro')>Otro (especificar)</option>
                                    </select>
                                    <p class="mt-1 text-xs text-white/50">
                                        Si no coincide con ninguna, elige <strong>Otro (especificar)</strong>.
                                    </p>
                                </div>
                                <div>
                                    <label for="observacion" class="block text-sm font-medium text-white/90 mb-2">
                                        Observación de cierre
                                        <span id="badge-observacion"
                                            class="ml-1 text-xs font-normal text-white/50">(Opcional)</span>
                                    </label>
                                    <textarea name="observacion" id="observacion" rows="4"
                                        placeholder="Describe brevemente la solución aplicada..."
                                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-base text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all">{{ old('observacion') }}</textarea>
                                </div>
                                <button type="submit"
                                    class="w-full py-3 px-4 rounded-xl bg-green-600 hover:bg-green-500 text-white font-bold shadow-lg shadow-green-500/20 transition-all">
                                    Cerrar Ticket
                                </button>
                            </form>

                            <details class="pt-4 border-t border-white/10 group">
                                <summary
                                    class="text-xs font-medium text-white/60 hover:text-white cursor-pointer list-none flex items-center justify-between">
                                    <span>Reasignar técnico</span>
                                    <svg class="w-4 h-4 group-open:rotate-180 transition-transform" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </summary>
                                <form action="{{ route('tickets.asignar', $ticket) }}" method="POST"
                                    class="mt-3 space-y-3">
                                    @csrf
                                    <select name="asignado_a" required
                                        class="w-full px-3 py-2.5 bg-white/5 border border-white/20 rounded-xl text-base sm:text-sm text-white focus:border-blue-400 outline-none">
                                        @foreach ($tecnicos as $tecnico)
                                            <option value="{{ $tecnico->id }}" @selected($ticket->asignado_a == $tecnico->id)>
                                                {{ $tecnico->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit"
                                        class="w-full py-3 px-3 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-semibold transition-all">
                                        Actualizar Técnico
                                    </button>
                                </form>
                            </details>
                        @else
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10 text-white/70 text-xs">
                                Solo lectura: ticket asignado a {{ $ticket->tecnicoAsignado->name ?? 'otro técnico' }}.
                            </div>
                        @endcan
                    @endif
                    @can('bienes.crear')
                        @if ($ticket->tipo_problema === 'Insumos' && !$ticket->estaCerrado())
                            <div class="mb-6">
                                <a href="{{ route('movimientos.create', ['funcionario_id' => $ticket->funcionario_id]) }}"
                                    class="text-white/70 hover:text-white/100 font-medium text-xs">Ir a Movimientos para
                                    prestar/transferir el insumo solicitado</a>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>

            <!-- Historial del Ticket -->
            <div class="md:col-span-2 bg-slate-900/60 border border-white/10 rounded-2xl p-6 shadow-xl">
                <h2 class="text-lg font-bold text-white border-b border-white/10 pb-3 mb-4">Historial de Actividad</h2>
                <div class="space-y-3">
                    @forelse ($ticket->historial as $evento)
                        <div
                            class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 sm:gap-4 p-3 rounded-xl bg-white/5 border border-white/10">
                            <div>
                                <p class="text-sm font-medium text-white">{{ $evento->accion }}</p>
                                <p class="text-xs text-white/50 mt-0.5">
                                    Por {{ $evento->responsable->name ?? ($evento->funcionario->nombre ?? 'Usuario') }}
                                </p>
                            </div>
                            <span class="text-xs text-white/60 whitespace-nowrap">
                                {{ ($evento->created_at ?? $evento->fecha)?->format('d/m/Y H:i') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-white/40 italic">Sin movimientos registrados aún.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const solucionSelect = document.getElementById('solucion_id');
            const observacion = document.getElementById('observacion');
            const badge = document.getElementById('badge-observacion');

            if (!solucionSelect || !observacion || !badge) {
                return;
            }

            function actualizarObservacion() {
                const esOtro = solucionSelect.value === 'otro' || solucionSelect.value === '';
                observacion.required = esOtro;

                if (esOtro) {
                    badge.textContent = '(Obligatorio para Otro)';
                    badge.className = 'ml-1 text-xs font-medium text-[#FEDB39]';
                    observacion.placeholder = 'Especifica la solución aplicada...';
                } else {
                    badge.textContent = '(Opcional)';
                    badge.className = 'ml-1 text-xs font-normal text-white/50';
                    observacion.placeholder = 'Puedes añadir una observación adicional (opcional)...';
                }
            }

            solucionSelect.addEventListener('change', actualizarObservacion);
            actualizarObservacion();
        });
    </script>
@endsection
