@extends('layouts.app')

@section('title', 'Crear Ticket Manual')

@section('content')
    @php
        $input = 'w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all';

        $coloresTipo = [
            'Equipo/Dispositivo' => '#60a5fa',
            'Programa/Sistema' => '#818cf8',
            'Internet' => '#1CD6CE',
            'Impresora/Escáner' => '#FEDB39',
            'Cuentas y Accesos' => '#a78bfa',
            'Consulta' => '#4ade80',
            'Insumos' => '#fbbf24',
        ];
    @endphp

    <div class="w-full max-w-4xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('tickets.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all"
                title="Volver al listado">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
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

        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('tickets.store-manual') }}" method="POST" id="form-ticket-manual" class="space-y-6">
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
                                <input type="radio" name="tipo_problema" value="{{ $tipo }}"
                                    class="peer sr-only" @checked(old('tipo_problema') === $tipo) required>
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

                <!-- Título del problema -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="descripcion_problema" class="text-sm font-medium text-white/90">
                            Título del problema
                        </label>
                        <span id="contador-palabras" class="text-xs text-white/50">0 / 100 palabras</span>
                    </div>
                    <input type="text" id="descripcion_problema" name="descripcion_problema" maxlength="255"
                        value="{{ old('descripcion_problema') }}"
                        placeholder="Ej. PC no enciende en Dirección Administrativa"
                        class="{{ $input }}" required>
                    <p class="mt-1 text-xs text-white/50">
                        Describe brevemente lo que ocurre (máximo 100 palabras).
                    </p>
                </div>

                <!-- Botones -->
                <div class="w-full flex items-center justify-end gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('tickets.index') }}"
                        class="px-8 py-3 bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all flex items-center gap-2 cursor-pointer">
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('descripcion_problema');
            const contador = document.getElementById('contador-palabras');

            if (!input || !contador) {
                return;
            }

            function actualizarContador() {
                const palabras = input.value.trim().split(/\s+/).filter(Boolean).length;
                contador.textContent = `${palabras} / 100 palabras`;
                contador.classList.toggle('text-red-400', palabras > 100);
                contador.classList.toggle('text-white/50', palabras <= 100);
                input.setCustomValidity(palabras > 100 ?
                    'El título del problema no puede superar las 100 palabras.' : '');
            }

            input.addEventListener('input', actualizarContador);
            actualizarContador();
        });
    </script>
@endsection
