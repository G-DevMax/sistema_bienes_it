@php
    $tipoActual = old('tipo_problema', $solucion->tipo_problema ?? '');
@endphp

<div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
    <form action="{{ $action }}" method="POST">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <!-- Tipo de Problema -->
        <div class="mb-6">
            <label for="tipo_problema" class="block mb-2 text-sm font-medium text-white/90">Tipo de Problema</label>
            <select id="tipo_problema" name="tipo_problema"
                class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                required>
                <option value="" disabled {{ $tipoActual ? '' : 'selected' }}>Selecciona un tipo de problema</option>
                @foreach ($tiposProblema as $tipo)
                    <option value="{{ $tipo }}" {{ $tipoActual === $tipo ? 'selected' : '' }}>
                        {{ $tipo }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Solución Común -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <label for="solucion" class="text-sm font-medium text-white/90">Solución Común</label>
                <span id="contador-palabras-solucion" class="text-xs text-white/50">0 / 100 palabras</span>
            </div>
            <textarea id="solucion" name="solucion" rows="3" maxlength="255"
                class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                placeholder="Ej. Se reinició el servicio de red y se verificó conectividad" required>{{ old('solucion', $solucion->solucion ?? '') }}</textarea>
        </div>

        <!-- Botones -->
        <div class="w-full flex items-center justify-start gap-4 mt-8 pt-6 border-t border-white/10">
            <a href="{{ route('detalles-soluciones.index') }}"
                class="px-8 py-3 bg-white/10 hover:bg-white/15 border border-white/15 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancelar
            </a>
            <button type="submit"
                class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:shadow-[0_0_25px_rgba(16,185,129,0.5)] transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                {{ $submitText }}
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const solucionInput = document.getElementById('solucion');
        const contador = document.getElementById('contador-palabras-solucion');

        function actualizarPalabras() {
            const total = solucionInput.value.trim().split(/\s+/).filter(Boolean).length;
            contador.textContent = `${total} / 100 palabras`;
            contador.classList.toggle('text-red-400', total > 100);
            contador.classList.toggle('text-white/50', total <= 100);
            solucionInput.setCustomValidity(total > 100 ? 'La solución común no puede superar las 100 palabras.' : '');
        }

        solucionInput.addEventListener('input', actualizarPalabras);
        actualizarPalabras();
    });
</script>
