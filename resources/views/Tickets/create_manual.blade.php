@extends('layouts.app')

@section('title', 'Crear Ticket Manual')

@section('content')
    @php
        $input =
            'w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all';

        $coloresTipo = [
            'Equipo/Dispositivo' => '#60a5fa',
            'Programa/Sistema' => '#818cf8',
            'Internet' => '#1CD6CE',
            'Impresora/Escáner' => '#FEDB39',
            'Cuentas y Accesos' => '#a78bfa',
            'Asistencia a Reunión' => '#f472b6',
            'Consulta' => '#4ade80',
            'Insumos' => '#fbbf24',
        ];
    @endphp

    <div class="w-full max-w-4xl">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('tickets.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all"
                title="Volver al listado">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Crear Ticket Manual
                </h1>
                <p class="text-white/70 mt-1">Registra una solicitud de soporte en nombre de un funcionario</p>
            </div>
        </div>

        <div class="mb-4">
            @include('layouts.alerts')
        </div>

        <div class="bg-slate-900/60 border border-white/10 rounded-2xl p-5 sm:p-8 shadow-xl">
            <form action="{{ route('tickets.store-manual') }}" method="POST" enctype="multipart/form-data"
                id="form-ticket-manual" class="space-y-6">
                @csrf

                <!-- Funcionario solicitante -->
                <div>
                    <label for="funcionario_id" class="block mb-2 text-sm font-medium text-white/90">
                        Funcionario solicitante
                    </label>
                    <select id="funcionario_id" name="funcionario_id"
                        class="searchable-select {{ $input }} appearance-none"
                        placeholder="Selecciona un funcionario..." required>
                        <option value="" disabled @selected(!old('funcionario_id'))>Selecciona un funcionario...</option>
                        @foreach ($funcionarios as $funcionario)
                            <option value="{{ $funcionario->id }}" @selected((string) old('funcionario_id') === (string) $funcionario->id)>
                                {{ $funcionario->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tipo de problema -->
                <fieldset>
                    <legend class="block mb-2 text-sm font-medium text-white/90">Tipo de problema</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach ($tiposProblema as $tipo)
                            @php $color = $coloresTipo[$tipo] ?? '#60a5fa'; @endphp
                            <label class="relative cursor-pointer block">
                                <input type="radio" name="tipo_problema" value="{{ $tipo }}" class="peer sr-only"
                                    @checked(old('tipo_problema') === $tipo) required>
                                <div class="flex items-center gap-3 px-4 py-2.5 rounded-full border border-white/15 bg-white/5 transition-all hover:bg-white/10 peer-checked:bg-white/10"
                                    style="--c: {{ $color }}" data-card>
                                    <span
                                        class="w-4 h-4 rounded-full border-2 border-white/40 flex items-center justify-center shrink-0 radio-ring">
                                        <span class="w-2 h-2 rounded-full transition-colors radio-dot"></span>
                                    </span>
                                    <span class="text-sm font-medium text-white">{{ $tipo }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <!-- Problema común o descripción personalizada -->
                @php
                    $detallesJson = ($detallesProblemas ?? collect())
                        ->map(
                            fn($d) => [
                                'id' => $d->id,
                                'tipo_problema' => $d->tipo_problema,
                                'detalle' => $d->detalle,
                            ],
                        )
                        ->values();
                    $tipoOld = old('tipo_problema');
                    $esReunionOld = $tipoOld === \App\Models\Tickets\Ticket::TIPO_ASISTENCIA_REUNION;
                    $esConsultaOld = $tipoOld === \App\Models\Tickets\Ticket::TIPO_CONSULTA;
                    $esInsumosOld = $tipoOld === \App\Models\Tickets\Ticket::TIPO_INSUMOS;
                    $detalleOld = $esConsultaOld
                        ? 'otro'
                        : old('detalle_id', old('descripcion_problema') ? 'otro' : '');
                    $mostrarCantidadOld = $esInsumosOld && $detalleOld !== '';
                @endphp
                <div id="bloque-titulo-problema" @class(['space-y-4', 'hidden' => $esReunionOld])>
                    <div id="bloque-select-detalle" @class(['hidden' => $esConsultaOld])>
                        <label for="detalle_id" class="block mb-2 text-sm font-medium text-white/90">
                            ¿Cuál es el problema?
                        </label>
                        <select id="detalle_id" name="detalle_id" data-detalles='@json($detallesJson)'
                            data-old-detalle="{{ $detalleOld }}"
                            class="{{ $input }} disabled:opacity-60 disabled:cursor-not-allowed"
                            required>
                            <option value="">Primero selecciona un tipo de problema...</option>
                            <option value="otro" @selected($detalleOld === 'otro')>Otro (especificar)</option>
                        </select>
                        <p class="mt-1 text-xs text-white/50">
                            Selecciona una opción común o elige <strong>Otro (especificar)</strong> para describir el
                            problema.
                        </p>
                    </div>

                    <div id="bloque-descripcion-otro" @class(['hidden' => $detalleOld !== 'otro'])>
                        <div class="flex items-center justify-between mb-2">
                            <label for="descripcion_problema" class="text-sm font-medium text-white/90">
                                Título del problema
                            </label>
                            <span id="contador-palabras" class="text-xs text-white/50">0 / 100 palabras</span>
                        </div>
                        <input type="text" id="descripcion_problema" name="descripcion_problema" maxlength="255"
                            value="{{ old('descripcion_problema') }}"
                            placeholder="Ej. PC no enciende en Dirección Administrativa" class="{{ $input }}"
                            @disabled($detalleOld !== 'otro')>
                        <p class="mt-1 text-xs text-white/50">
                            Describe brevemente lo que ocurre (máximo 100 palabras).
                        </p>
                    </div>

                    <div id="bloque-cantidad-insumo" @class(['hidden' => !$mostrarCantidadOld])>
                        <label for="cantidad_insumo" class="block mb-2 text-sm font-medium text-white/90">
                            Especificar cantidad
                        </label>
                        <input type="number" id="cantidad_insumo" name="cantidad_insumo" min="1" max="12"
                            step="1" value="{{ old('cantidad_insumo', 1) }}" class="{{ $input }}"
                            @disabled(!$mostrarCantidadOld)>
                        <p class="mt-1 text-xs text-white/50">
                            Cantidad solicitada (entre 1 y 12).
                        </p>
                    </div>
                </div>

                <!-- Campos adicionales para Asistencia a Reunión -->
                <div id="campos-reunion" class="hidden space-y-5 pt-4 border-t border-white/10">
                    <div>
                        <label for="fecha_reunion" class="block mb-2 text-sm font-medium text-white/90">
                            Fecha de la reunión
                        </label>
                        <input type="date" id="fecha_reunion" name="fecha_reunion"
                            min="{{ \App\Models\Tickets\Ticket::ANIO_MINIMO }}-01-01"
                            value="{{ old('fecha_reunion', now()->toDateString()) }}"
                            class="{{ $input }} [color-scheme:dark]" disabled>
                    </div>

                    <div>
                        <label for="horario_reunion" class="block mb-2 text-sm font-medium text-white/90">
                            Hora de comienzo y finalización aproximada
                        </label>
                        <input type="text" id="horario_reunion" name="horario_reunion" maxlength="100"
                            value="{{ old('horario_reunion') }}" placeholder="Ej. 09:00 a 11:00 aprox."
                            class="{{ $input }}" disabled>
                    </div>

                    <div>
                        <label for="detalles_reunion" class="block mb-2 text-sm font-medium text-white/90">
                            Detalles de la reunión/evento
                        </label>
                        <input type="text" id="detalles_reunion" name="detalles_reunion" maxlength="500"
                            value="{{ old('detalles_reunion') }}"
                            placeholder="Ej. Salón Multiuso - se requiere proyector y audio" class="{{ $input }}"
                            disabled>
                        <p class="mt-1 text-xs text-white/50">
                            Indica dónde será la reunión y observaciones necesarias.
                        </p>
                    </div>
                </div>

                <!-- Adjuntar foto del problema (visible en celular vía JS) -->
                <div id="contenedor-foto-movil" class="hidden space-y-2">
                    <input type="file" id="imagen_problema" name="imagen_problema" accept="image/*"
                        capture="environment" class="hidden">
                    <button type="button" id="btn-adjuntar-foto"
                        class="w-full py-3 px-4 rounded-xl bg-white/5 hover:bg-white/10 border border-white/20 hover:border-blue-400 text-white/90 font-semibold text-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5 text-blue-400 shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Adjuntar foto del problema</span>
                    </button>
                    <p id="estado-foto-movil" class="hidden text-xs text-emerald-400 text-center font-medium"></p>
                </div>

                <!-- Botones -->
                <div class="w-full flex flex-col-reverse sm:flex-row items-center justify-end gap-3 sm:gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('tickets.index') }}"
                        class="w-full sm:w-auto justify-center px-8 py-3 bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        Cancelar
                    </a>
                    <button type="submit"
                        class="w-full sm:w-auto justify-center px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all flex items-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Crear Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        input:checked+[data-card] {
            border-color: var(--c);
            box-shadow: 0 0 16px color-mix(in srgb, var(--c) 35%, transparent);
        }

        input:checked+[data-card] .radio-ring {
            border-color: var(--c);
        }

        input:checked+[data-card] .radio-dot {
            background-color: var(--c);
        }
    </style>
@endsection
