@php
    $input =
        'w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed';

    $coloresTipo = [
        'Hardware' => '#60a5fa',
        'Software' => '#818cf8',
        'Red' => '#1CD6CE',
        'Impresora' => '#FEDB39',
        'Cuentas y Accesos' => '#a78bfa',
        'Consulta' => '#4ade80',
    ];

    $esEdicion = isset($informe);
    $bloqueadoPorTicket = $esEdicion && $informe->vieneDeTicket();
    $tipoActual = old('tipo_problema', $informe->tipo_problema ?? ($tiposProblema[0] ?? 'Hardware'));
    $funcionarioActual = old('funcionario_id', $informe->funcionario_id ?? '');

    if ($esEdicion) {
        $detalleInicial = old('detalle_id', $informe->detalle_id ?? 'otro');
        $solucionInicial = old('solucion_id', $informe->solucion_id ?? 'otro');
    } else {
        $detalleInicial = old('detalle_id', '');
        $solucionInicial = old('solucion_id', '');
    }

    $detallesJson = $detalles
        ->map(
            fn($d) => [
                'id' => $d->id,
                'tipo_problema' => $d->tipo_problema,
                'detalle' => $d->detalle,
            ],
        )
        ->values();

    $solucionesJson = ($soluciones ?? collect())
        ->map(
            fn($s) => [
                'id' => $s->id,
                'tipo_problema' => $s->tipo_problema,
                'solucion' => $s->solucion,
            ],
        )
        ->values();
@endphp

<div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
    @if ($bloqueadoPorTicket)
        <div
            class="mb-6 p-4 rounded-xl bg-blue-500/10 border border-blue-400/30 text-sm text-blue-200 flex items-center justify-between gap-4">
            <span>Este informe fue generado desde el <strong>Ticket #{{ $informe->ticket?->numero_ticket ?? $informe->ticket_id }}</strong>. El funcionario
                y el tipo de problema están bloqueados para mantener la coherencia.</span>
            @can('tickets.ver')
                <a href="{{ route('tickets.show', $informe->ticket_id) }}"
                    class="shrink-0 text-xs font-semibold text-[#1CD6CE] hover:underline">Ver Ticket</a>
            @endcan
        </div>
    @endif
    <form action="{{ $action }}" method="POST" id="form-informe">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Título corto -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center justify-between mb-2">
                    <label for="titulo" class="text-sm font-medium text-white/90">Título corto del problema</label>
                    <span id="contador-palabras" class="text-xs text-white/50">0 / 100 palabras</span>
                </div>
                <input type="text" id="titulo" name="titulo" maxlength="500"
                    value="{{ old('titulo', $informe->titulo ?? '') }}"
                    placeholder="Ej. PC no enciende en Dirección Administrativa" class="{{ $input }}" required>
            </div>

            <!-- Funcionario atendido -->
            <div>
                <label for="funcionario_id" class="block mb-2 text-sm font-medium text-white/90">Funcionario
                    atendido</label>
                <select id="funcionario_id" name="funcionario_id" @disabled($bloqueadoPorTicket)
                    class="searchable-select {{ $input }} appearance-none"
                    placeholder="Selecciona un funcionario..." required>
                    <option value="" disabled {{ $funcionarioActual ? '' : 'selected' }}>Selecciona un funcionario
                    </option>
                    @foreach ($funcionarios as $func)
                        <option value="{{ $func->id }}"
                            {{ (string) $funcionarioActual === (string) $func->id ? 'selected' : '' }}>
                            {{ $func->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Fecha y hora de registro (no editable) -->
            <div>
                <label for="fecha_hora" class="block mb-2 text-sm font-medium text-white/90">
                    Fecha y hora de registro <span class="text-xs font-normal text-white/50">(No editable)</span>
                </label>
                <input type="datetime-local" id="fecha_hora" value="{{ $fechaHora?->format('Y-m-d\TH:i') }}" disabled
                    class="{{ $input }}">
                <p class="mt-1 text-xs text-white/50">
                    {{ $esEdicion ? 'Se conserva la fecha y hora original del informe.' : 'Se registra automáticamente con tu fecha y hora actual.' }}
                </p>
            </div>

            <!-- Tipo de problema (botones round en columna) -->
            <fieldset class="col-span-1">
                <legend class="block mb-2 text-sm font-medium text-white/90">Tipo de problema</legend>
                <div class="flex flex-col gap-2.5">
                    @foreach ($tiposProblema as $tipo)
                        @php $color = $coloresTipo[$tipo] ?? '#60a5fa'; @endphp
                        <label
                            class="relative {{ $bloqueadoPorTicket ? 'cursor-not-allowed opacity-70' : 'cursor-pointer' }} block">
                            <input type="radio" name="tipo_problema" value="{{ $tipo }}" class="peer sr-only"
                                {{ $tipoActual === $tipo ? 'checked' : '' }} @disabled($bloqueadoPorTicket) required>
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

            <!-- Detalle del problema y Observaciones -->
            <div class="col-span-1 flex flex-col gap-6">
                <div>
                    <label for="detalle_id" class="block mb-2 text-sm font-medium text-white/90">Detalle del
                        problema</label>
                    <select id="detalle_id" name="detalle_id" class="{{ $input }} appearance-none"
                        placeholder="Escribe para buscar un detalle o elige Otro...">
                        <option value="">Selecciona o busca un detalle...</option>
                    </select>
                    <p class="mt-1 text-xs text-white/50">
                        Si no existe un detalle general para el caso, selecciona <strong>Otro (especificar)</strong>.
                    </p>
                </div>

                <div>
                    <label for="solucion_id" class="block mb-2 text-sm font-medium text-white/90">Solución común</label>
                    <select id="solucion_id" name="solucion_id" class="{{ $input }} appearance-none"
                        placeholder="Escribe para buscar una solución o elige Otro...">
                        <option value="">Selecciona o busca una solución...</option>
                    </select>
                    <p class="mt-1 text-xs text-white/50">
                        Si no existe una solución común para el caso, selecciona <strong>Otro (especificar)</strong>.
                    </p>
                </div>

                <div class="flex-1 flex flex-col">
                    <label for="observacion" class="block mb-2 text-sm font-medium text-white/90">
                        Observaciones y solución
                        <span id="badge-observacion" class="ml-1 text-xs font-normal text-white/50">(Opcional)</span>
                    </label>
                    <textarea id="observacion" name="observacion" rows="6" class="{{ $input }} flex-1"
                        placeholder="Describe cómo se solucionó el problema...">{{ old('observacion', $informe->observacion ?? '') }}</textarea>
                    <p id="ayuda-observacion" class="mt-1 text-xs text-white/50">
                        Puedes indicar qué acciones se realizaron para resolver el problema.
                    </p>
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="w-full flex items-center justify-end gap-4 mt-8 pt-6 border-t border-white/10">
            <a href="{{ route('informes.index') }}"
                class="px-8 py-3 bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Cancelar
            </a>
            <button type="submit"
                class="px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $submitText }}
            </button>
        </div>
    </form>
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
        const detallesData = @json($detallesJson);
        const solucionesData = @json($solucionesJson);
        const detalleInicial = @json((string) $detalleInicial);
        const solucionInicial = @json((string) $solucionInicial);

        const tituloInput = document.getElementById('titulo');
        const contadorPalabras = document.getElementById('contador-palabras');
        const detalleSelect = document.getElementById('detalle_id');
        const solucionSelect = document.getElementById('solucion_id');
        const observacionInput = document.getElementById('observacion');
        const badgeObservacion = document.getElementById('badge-observacion');
        const ayudaObservacion = document.getElementById('ayuda-observacion');
        const radiosTipo = document.querySelectorAll('input[name="tipo_problema"]');

        // Contador de palabras + validación nativa antes del submit
        function actualizarPalabras() {
            const palabras = tituloInput.value.trim().split(/\s+/).filter(Boolean);
            const total = palabras.length;
            contadorPalabras.textContent = `${total} / 100 palabras`;
            contadorPalabras.classList.toggle('text-red-400', total > 100);
            contadorPalabras.classList.toggle('text-white/50', total <= 100);
            tituloInput.setCustomValidity(total > 100 ? 'El título no puede superar las 100 palabras.' : '');
        }
        tituloInput.addEventListener('input', actualizarPalabras);
        actualizarPalabras();

        // TomSelect para detalle del problema y solución común (buscables al escribir)
        const tsDetalle = window.TomSelect ?
            new window.TomSelect(detalleSelect, {
                create: false,
                placeholder: 'Escribe para buscar un detalle o elige Otro...',
                sortField: [{
                    field: '$order'
                }]
            }) :
            null;

        const tsSolucion = window.TomSelect ?
            new window.TomSelect(solucionSelect, {
                create: false,
                placeholder: 'Escribe para buscar una solución o elige Otro...',
                sortField: [{
                    field: '$order'
                }]
            }) :
            null;

        function valorSelect(ts, el) {
            return ts ? ts.getValue() : el.value;
        }

        function actualizarEstadoObservacion() {
            const valDetalle = valorSelect(tsDetalle, detalleSelect);
            const valSolucion = valorSelect(tsSolucion, solucionSelect);
            const ambosOtro = (valDetalle === 'otro' || valDetalle === '') && (valSolucion === 'otro' ||
                valSolucion === '');
            observacionInput.required = ambosOtro;

            if (ambosOtro) {
                badgeObservacion.textContent = '(Obligatorio para Otro)';
                badgeObservacion.className = 'ml-1 text-xs font-medium text-[#FEDB39]';
                ayudaObservacion.textContent =
                    'Especifica tanto el problema ocurrido como la solución aplicada.';
                observacionInput.placeholder = 'Describe el problema y cómo lo solucionaste...';
            } else {
                badgeObservacion.textContent = '(Opcional)';
                badgeObservacion.className = 'ml-1 text-xs font-normal text-white/50';
                ayudaObservacion.textContent = 'Puedes indicar cómo se solucionó este problema.';
                observacionInput.placeholder = 'Describe cómo se solucionó el problema...';
            }
        }

        function cargarOpcionesPorTipo(selectEl, ts, items, textoKey, placeholder, tipo, valorPorSeleccionar) {
            const filtrados = items.filter(item => item.tipo_problema === tipo);

            if (ts) {
                ts.clear(true);
                ts.clearOptions();
                filtrados.forEach(item => ts.addOption({
                    value: String(item.id),
                    text: item[textoKey]
                }));
                ts.addOption({
                    value: 'otro',
                    text: 'Otro (especificar)'
                });
                ts.refreshOptions(false);

                const existe = valorPorSeleccionar && (
                    valorPorSeleccionar === 'otro' ||
                    filtrados.some(item => String(item.id) === String(valorPorSeleccionar))
                );

                if (existe) {
                    ts.setValue(String(valorPorSeleccionar), true);
                } else if (filtrados.length === 0) {
                    ts.setValue('otro', true);
                }
            } else {
                selectEl.innerHTML = `<option value="">${placeholder}</option>`;
                filtrados.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = String(item.id);
                    opt.textContent = item[textoKey];
                    selectEl.appendChild(opt);
                });
                const optOtro = document.createElement('option');
                optOtro.value = 'otro';
                optOtro.textContent = 'Otro (especificar)';
                selectEl.appendChild(optOtro);

                if (valorPorSeleccionar) {
                    selectEl.value = String(valorPorSeleccionar);
                } else if (filtrados.length === 0) {
                    selectEl.value = 'otro';
                }
            }
        }

        function cargarCatalogosPorTipo(tipo, valDetalle, valSolucion) {
            cargarOpcionesPorTipo(detalleSelect, tsDetalle, detallesData, 'detalle',
                'Selecciona o busca un detalle...', tipo, valDetalle);
            cargarOpcionesPorTipo(solucionSelect, tsSolucion, solucionesData, 'solucion',
                'Selecciona o busca una solución...', tipo, valSolucion);
            actualizarEstadoObservacion();
        }

        radiosTipo.forEach(radio => {
            radio.addEventListener('change', () => {
                cargarCatalogosPorTipo(radio.value, null, null);
            });
        });

        if (tsDetalle) {
            tsDetalle.on('change', actualizarEstadoObservacion);
        } else {
            detalleSelect.addEventListener('change', actualizarEstadoObservacion);
        }

        if (tsSolucion) {
            tsSolucion.on('change', actualizarEstadoObservacion);
        } else {
            solucionSelect.addEventListener('change', actualizarEstadoObservacion);
        }

        const tipoMarcado = document.querySelector('input[name="tipo_problema"]:checked')?.value;
        if (tipoMarcado) {
            cargarCatalogosPorTipo(tipoMarcado, detalleInicial, solucionInicial);
        }
    });
</script>
