@extends('layouts.app')

@section('title', 'Registrar Movimiento')

@section('content')
    @php
        $input = 'w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed';
        $tipoActual = old('tipo_movimiento', $tipoSeleccionado);
        $itemActual = old('item_id', $itemSeleccionado);
        $tipos = [
            'prestamo' => ['Préstamo', 'Uso temporal con fecha prevista de devolución', '#FEDB39'],
            'transferencia' => ['Transferencia', 'Asignación casi permanente a un funcionario', '#a78bfa'],
            'baja' => ['Baja', 'El item se dañó o quedó inservible (desde Informática)', '#D61C4E'],
        ];
    @endphp
    <div class="w-full max-w-4xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('movimientos.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Registrar Movimiento
                </h1>
                <p class="text-white/70 mt-1">El stock del item se actualiza automáticamente al guardar</p>
            </div>
        </div>

        <div class="mb-4">
            @include('layouts.alerts')
        </div>

        <!-- Formulario -->
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('movimientos.store') }}" method="POST" id="form-movimiento">
                @csrf

                <!-- Tipo de Movimiento -->
                <fieldset class="mb-6">
                    <legend class="block mb-3 text-sm font-medium text-white/90">Tipo de Movimiento</legend>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach ($tipos as $valor => [$etiqueta, $ayuda, $color])
                            <label class="relative cursor-pointer">
                                <input type="radio" name="tipo_movimiento" value="{{ $valor }}" class="peer sr-only"
                                    {{ $tipoActual === $valor ? 'checked' : '' }}>
                                <div class="h-full p-4 rounded-xl border border-white/10 bg-white/5 transition-all hover:bg-white/10 peer-checked:bg-white/10 peer-checked:shadow-lg"
                                    style="--c: {{ $color }}" data-card>
                                    <span class="block font-semibold" style="color: {{ $color }}">{{ $etiqueta }}</span>
                                    <span class="block text-xs text-white/60 mt-1">{{ $ayuda }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Item -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="item_id" class="block mb-2 text-sm font-medium text-white/90">Bien / Item</label>
                        <select id="item_id" name="item_id" class="{{ $input }} appearance-none" required>
                            <option value="" disabled {{ $itemActual ? '' : 'selected' }}>Selecciona un bien</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}"
                                    data-serial="{{ $item->esSerializado() ? 1 : 0 }}"
                                    data-disponible="{{ $item->stock_disponible }}"
                                    {{ (string) $itemActual === (string) $item->id ? 'selected' : '' }}>
                                    {{ $item->descripcion }} —
                                    {{ $item->esSerializado() ? 'Cód. ' . $item->codigo_patrimonial : 'Disponibles: ' . $item->stock_disponible }}
                                </option>
                            @endforeach
                        </select>
                        <p id="item-ayuda" class="mt-2 text-xs text-white/50">Solo se listan items con unidades disponibles en Informática.</p>
                    </div>

                    <!-- Funcionario -->
                    <div class="col-span-1 md:col-span-2" data-campo="funcionario">
                        <label for="funcionario_id" class="block mb-2 text-sm font-medium text-white/90">Funcionario Receptor</label>
                        <select id="funcionario_id" name="funcionario_id" class="{{ $input }} appearance-none">
                            <option value="" disabled {{ old('funcionario_id') ? '' : 'selected' }}>Selecciona un funcionario</option>
                            @foreach ($funcionarios as $func)
                                <option value="{{ $func->id }}" {{ old('funcionario_id') == $func->id ? 'selected' : '' }}>{{ $func->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cantidad -->
                    <div>
                        <label for="cantidad" class="block mb-2 text-sm font-medium text-white/90">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" value="{{ old('cantidad', 1) }}" min="1"
                            class="{{ $input }}" required>
                    </div>

                    <!-- Fecha de Movimiento -->
                    <div>
                        <label for="fecha_movimiento" class="block mb-2 text-sm font-medium text-white/90">Fecha de Movimiento</label>
                        <input type="datetime-local" id="fecha_movimiento" name="fecha_movimiento"
                            value="{{ old('fecha_movimiento', now()->format('Y-m-d\TH:i')) }}" class="{{ $input }}" required>
                    </div>

                    <!-- Fecha prevista de devolución (solo préstamo) -->
                    <div data-campo="prestamo">
                        <label for="fecha_devolucion_prevista" class="block mb-2 text-sm font-medium text-white/90">Fecha prevista de devolución</label>
                        <input type="date" id="fecha_devolucion_prevista" name="fecha_devolucion_prevista"
                            value="{{ old('fecha_devolucion_prevista') }}" class="{{ $input }}">
                        <p class="mt-1 text-xs text-white/50">Obligatoria. Si el pedido es informal, colocá una fecha estimada para el seguimiento.</p>
                    </div>

                    <!-- Motivo (solo baja) -->
                    <div class="col-span-1 md:col-span-2" data-campo="baja">
                        <label for="motivo" class="block mb-2 text-sm font-medium text-white/90">Motivo de la baja</label>
                        <input type="text" id="motivo" name="motivo" value="{{ old('motivo') }}" maxlength="255"
                            class="{{ $input }}" placeholder="Ej. Pantalla rota, no enciende, obsoleto...">
                    </div>

                    <!-- Observaciones -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="observaciones" class="block mb-2 text-sm font-medium text-white/90">Observaciones <span class="text-white/50 text-xs font-normal">(Opcional)</span></label>
                        <textarea id="observaciones" name="observaciones" rows="2" class="{{ $input }}"
                            placeholder="Detalles adicionales">{{ old('observaciones') }}</textarea>
                    </div>
                </div> <!-- Fin del grid -->

                <!-- Botones -->
                <div class="w-full flex items-center justify-end gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('movimientos.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-bold rounded-xl shadow-blue-500/30 hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardar Movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* borde del tipo seleccionado con el color de cada tarjeta */
        input:checked + [data-card] { border-color: var(--c); box-shadow: 0 0 18px color-mix(in srgb, var(--c) 35%, transparent); }
    </style>

    <script>
        (function () {
            const form = document.getElementById('form-movimiento');
            const itemSelect = document.getElementById('item_id');
            const cantidad = document.getElementById('cantidad');
            const ayuda = document.getElementById('item-ayuda');

            // muestra/oculta campos segun tipo; los campos ocultos se deshabilitan para no enviarse
            function mostrarCampo(nombre, visible) {
                form.querySelectorAll('[data-campo="' + nombre + '"]').forEach(function (el) {
                    el.classList.toggle('hidden', !visible);
                    el.querySelectorAll('input, select, textarea').forEach(function (c) {
                        c.disabled = !visible;
                        c.required = visible;
                    });
                });
            }

            function tipoActual() {
                const r = form.querySelector('input[name="tipo_movimiento"]:checked');
                return r ? r.value : 'prestamo';
            }

            function actualizarTipo() {
                const tipo = tipoActual();
                mostrarCampo('funcionario', tipo !== 'baja');
                mostrarCampo('prestamo', tipo === 'prestamo');
                mostrarCampo('baja', tipo === 'baja');
            }

            // serializado => cantidad fija 1; generico => max = disponibles
            function actualizarItem() {
                const opt = itemSelect.selectedOptions[0];
                if (!opt || !opt.value) return;
                const serial = opt.dataset.serial === '1';
                const disponible = parseInt(opt.dataset.disponible, 10);
                cantidad.max = serial ? 1 : disponible;
                if (serial) {
                    cantidad.value = 1;
                    cantidad.readOnly = true;
                    ayuda.textContent = 'Item único (con código patrimonial): la cantidad es siempre 1.';
                } else {
                    cantidad.readOnly = false;
                    if (parseInt(cantidad.value, 10) > disponible) cantidad.value = disponible;
                    ayuda.textContent = 'Disponibles en Informática: ' + disponible + ' unidad(es).';
                }
            }

            form.querySelectorAll('input[name="tipo_movimiento"]').forEach(function (r) {
                r.addEventListener('change', actualizarTipo);
            });
            itemSelect.addEventListener('change', actualizarItem);
            actualizarTipo();
            actualizarItem();
        })();
    </script>
@endsection
