<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Soporte Técnico | InfraDesk</title>
    <meta name="description" content="Formulario rápido para crear un ticket de soporte técnico">
    <link rel="icon" type="image/png" href="{{ asset('img/logo_app.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Sobrescribir estilos translúcidos de TomSelect para usar colores sólidos negro/azul-morado */
        .ticket-user-form .ts-control {
            background-color: #09090b !important;
            border: 1px solid #3f3f46 !important;
            border-radius: 0.75rem !important;
            color: #ffffff !important;
            padding: 0.8rem 2.5rem 0.8rem 1rem !important;
            min-height: 48px;
        }

        .ticket-user-form .ts-control.focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 2px #4338ca !important;
            background-color: #09090b !important;
        }

        .ticket-user-form .ts-dropdown {
            background-color: #18181b !important;
            border: 1px solid #3f3f46 !important;
            border-radius: 0.75rem !important;
            color: #f4f4f5 !important;
        }

        .ticket-user-form .ts-dropdown .option.active,
        .ticket-user-form .ts-dropdown .option:hover {
            background-color: #312e81 !important;
            color: #ffffff !important;
        }

        .ticket-user-form input[type="radio"]:checked+[data-pill] {
            background-color: #1e1b4b;
            border-color: #6366f1;
            color: #ffffff;
        }

        .ticket-user-form input[type="radio"]:checked+[data-pill] .radio-ring {
            border-color: #818cf8;
        }

        .ticket-user-form input[type="radio"]:checked+[data-pill] .radio-dot {
            background-color: #818cf8;
        }
    </style>
</head>

<body
    class="bg-neutral-950 text-zinc-100 min-h-screen flex flex-col justify-center px-4 py-6 sm:py-10 font-sans antialiased">
    <main class="w-full max-w-lg mx-auto ticket-user-form">
        <!-- Cabecera simple -->
        <header class="mb-6 text-center sm:text-left">
            <h1 class="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                Crear Ticket de Soporte
            </h1>
        </header>

        <!-- Alertas -->
        @include('layouts.alerts')

        <!-- Tarjeta del formulario en colores sólidos -->
        <section class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 sm:p-7 shadow-xl">
            <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data"
                id="form-ticket-usuario" class="space-y-6">
                @csrf

                <!-- 1. Buscador de funcionario con TomSelect -->
                <div>
                    <label for="funcionario_id" class="block mb-2 text-sm font-semibold text-zinc-200">
                        ¿Quién solicita el soporte?
                    </label>
                    <select id="funcionario_id" name="funcionario_id"
                        class="searchable-select w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white"
                        placeholder="Escribe tu nombre para buscar..." required>
                        <option value="" disabled @selected(!old('funcionario_id'))>Escribe tu nombre para buscar...
                        </option>
                        @foreach ($funcionarios as $funcionario)
                            <option value="{{ $funcionario->id }}" @selected((string) old('funcionario_id') === (string) $funcionario->id)>
                                {{ $funcionario->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Tipo de problema con botones round -->
                <fieldset>
                    <legend class="block mb-2.5 text-sm font-semibold text-zinc-200">
                        Tipo de problema
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        @foreach ($tiposProblema as $tipo)
                            <label class="relative cursor-pointer block">
                                <input type="radio" name="tipo_problema" value="{{ $tipo }}"
                                    class="peer sr-only" @checked(old('tipo_problema') === $tipo) required>
                                <div data-pill
                                    class="flex items-center gap-3 px-4 py-3 rounded-full bg-neutral-950 border border-zinc-700 text-zinc-200 hover:border-indigo-500 transition-colors">
                                    <span
                                        class="w-4 h-4 rounded-full border-2 border-zinc-500 flex items-center justify-center shrink-0 radio-ring">
                                        <span class="w-2 h-2 rounded-full bg-transparent radio-dot"></span>
                                    </span>
                                    <span class="text-sm font-medium">{{ $tipo }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <!-- 3. Problema común o descripción personalizada -->
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
                        <label for="detalle_id" class="block mb-2 text-sm font-semibold text-zinc-200">
                            ¿Cuál es tu problema?
                        </label>
                        <select id="detalle_id" name="detalle_id" data-detalles='@json($detallesJson)'
                            data-old-detalle="{{ $detalleOld }}"
                            class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                            required>
                            <option value="">Primero selecciona un tipo de problema...</option>
                            <option value="otro" @selected($detalleOld === 'otro')>Otro (especificar)</option>
                        </select>
                        <p class="mt-1.5 text-xs text-zinc-400">
                            Selecciona una opción común o elige <strong>Otro (especificar)</strong> para describir tu
                            problema.
                        </p>
                    </div>

                    <div id="bloque-descripcion-otro" @class(['hidden' => $detalleOld !== 'otro'])>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <label for="descripcion_problema" class="text-sm font-semibold text-zinc-200">
                                Describe brevemente tu problema
                            </label>
                            <span id="contador-palabras" class="text-xs font-medium text-zinc-400">
                                0 / 100 palabras
                            </span>
                        </div>
                        <input type="text" id="descripcion_problema" name="descripcion_problema" maxlength="255"
                            value="{{ old('descripcion_problema') }}"
                            placeholder="Ej. Mi impresora no imprime documentos"
                            class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors"
                            @disabled($detalleOld !== 'otro')>
                        <p class="mt-1.5 text-xs text-zinc-400">
                            Describe brevemente lo que ocurre (máximo 100 palabras).
                        </p>
                    </div>

                    <div id="bloque-cantidad-insumo" @class(['hidden' => !$mostrarCantidadOld])>
                        <label for="cantidad_insumo" class="block mb-2 text-sm font-semibold text-zinc-200">
                            Especificar cantidad
                        </label>
                        <input type="number" id="cantidad_insumo" name="cantidad_insumo" min="1" max="12"
                            step="1" value="{{ old('cantidad_insumo', 1) }}"
                            class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors"
                            @disabled(!$mostrarCantidadOld)>
                        <p class="mt-1.5 text-xs text-zinc-400">
                            Cantidad solicitada (entre 1 y 12).
                        </p>
                    </div>
                </div>

                <!-- 4. Campos adicionales para Asistencia a Reunión -->
                <div id="campos-reunion" class="hidden space-y-5 pt-3 border-t border-zinc-800">
                    <div>
                        <label for="fecha_reunion" class="block mb-2 text-sm font-semibold text-zinc-200">
                            Fecha de la reunión
                        </label>
                        <input type="date" id="fecha_reunion" name="fecha_reunion" min="{{ now()->toDateString() }}"
                            value="{{ old('fecha_reunion', now()->toDateString()) }}"
                            class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white [color-scheme:dark] focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors"
                            disabled>
                    </div>

                    <div>
                        <label for="horario_reunion" class="block mb-2 text-sm font-semibold text-zinc-200">
                            Hora de comienzo y finalización aproximada
                        </label>
                        <input type="text" id="horario_reunion" name="horario_reunion" maxlength="100"
                            value="{{ old('horario_reunion') }}" placeholder="Ej. 09:00 a 11:00 aprox."
                            class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors"
                            disabled>
                    </div>

                    <div>
                        <label for="detalles_reunion" class="block mb-2 text-sm font-semibold text-zinc-200">
                            Detalles de la reunión/evento
                        </label>
                        <input type="text" id="detalles_reunion" name="detalles_reunion" maxlength="500"
                            value="{{ old('detalles_reunion') }}"
                            placeholder="Ej. Salón Multiuso - se requiere proyector y audio"
                            class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors"
                            disabled>
                        <p class="mt-1.5 text-xs text-zinc-400">
                            Indica dónde será la reunión y observaciones necesarias.
                        </p>
                    </div>
                </div>

                <!-- 5. Adjuntar foto del problema (visible en celular vía JS) -->
                <div id="contenedor-foto-movil" class="hidden space-y-2">
                    <input type="file" id="imagen_problema" name="imagen_problema" accept="image/*"
                        capture="environment" class="hidden">
                    <button type="button" id="btn-adjuntar-foto"
                        class="w-full py-3 px-4 rounded-xl bg-neutral-950 hover:bg-zinc-800 border border-zinc-700 hover:border-indigo-500 text-zinc-200 font-semibold text-sm transition-colors flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5 text-indigo-400 shrink-0" fill="none" stroke="currentColor"
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

                <!-- Botón de envío -->
                <button type="submit"
                    class="w-full py-3.5 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:bg-violet-700 text-white font-bold text-base transition-colors cursor-pointer">
                    Enviar Ticket
                </button>
            </form>
        </section>

        <footer class="mt-6 text-center text-xs text-zinc-500">
            <p>© {{ date('Y') }} - InfraDesk (SGDI) | Departamento de Informática y TICs</p>
        </footer>
    </main>
</body>

</html>
