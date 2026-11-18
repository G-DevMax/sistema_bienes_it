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
            <form action="{{ route('tickets.store') }}" method="POST" id="form-ticket-usuario" class="space-y-6">
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

                <!-- 3. Título del problema (máximo 100 palabras) -->
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <label for="descripcion_problema" class="text-sm font-semibold text-zinc-200">
                            Título del problema
                        </label>
                        <span id="contador-palabras" class="text-xs font-medium text-zinc-400">
                            0 / 100 palabras
                        </span>
                    </div>
                    <input type="text" id="descripcion_problema" name="descripcion_problema" maxlength="255"
                        value="{{ old('descripcion_problema') }}" placeholder="Ej. Mi impresora no imprime documentos"
                        class="w-full px-4 py-3 bg-neutral-950 border border-zinc-700 rounded-xl text-white placeholder-zinc-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-600 transition-colors"
                        required>
                    <p class="mt-1.5 text-xs text-zinc-400">
                        Describe brevemente lo que ocurre (máximo 100 palabras).
                    </p>
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
                contador.classList.toggle('text-zinc-400', palabras <= 100);
                input.setCustomValidity(palabras > 100 ?
                    'El título del problema no puede superar las 100 palabras.' : '');
            }

            input.addEventListener('input', actualizarContador);
            actualizarContador();
        });
    </script>
</body>

</html>
