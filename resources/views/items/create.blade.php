@extends('layouts.app')

@section('title', 'Crear Nuevo Item')

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('items.index') }}"
                class="p-2 text-white/50 hover:text-[#1CD6CE] hover:bg-[#1CD6CE]/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#1CD6CE] to-[#FEDB39] bg-clip-text text-transparent">
                    Registrar Nuevo Item
                </h1>
                <p class="text-white/70 mt-1">Completa los datos para ingresar un nuevo item al sistema</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="mb-4">
            @include('layouts.alerts')
        </div>
        <div
            class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('items.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Nombre -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="descripcion" class="block mb-2 text-sm font-medium text-white/90">Nombre /
                            Descripción
                        </label>
                        <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion') }}"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                            placeholder="Ej. Laptop ThinkPad T14" required>
                    </div>

                    <!-- Categoría -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="categoria_id" class="block mb-2 text-sm font-medium text-white/90">Categoría</label>
                        <select id="categoria_id" name="categoria_id"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            required>
                            <option value="" disabled {{ old('categoria_id') ? '' : 'selected' }}>Selecciona una categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tipo de item -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="tiene_codigo" class="flex items-start gap-3 p-4 rounded-xl border border-white/10 bg-white/5 cursor-pointer hover:border-[#1CD6CE]/40 transition-colors">
                            <input type="hidden" name="tiene_codigo" value="0">
                            <input type="checkbox" id="tiene_codigo" name="tiene_codigo" value="1"
                                class="mt-1 w-5 h-5 accent-[#1CD6CE]" {{ old('tiene_codigo', '1') == '1' ? 'checked' : '' }}>
                            <span>
                                <span class="block font-medium text-white">Tiene código patrimonial</span>
                                <span class="block text-xs text-white/60 mt-0.5">
                                    Marcado: item único (ej. notebook), el stock es siempre 1.
                                    Desmarcado: item genérico (ej. cables HDMI), se controla por cantidad y el código figura como "No aplica".
                                </span>
                            </span>
                        </label>
                    </div>

                    <div id="campo_codigo">
                        <label for="codigo_patrimonial" class="block mb-2 text-sm font-medium text-white/90">Código Patrimonial</label>
                        <input type="text" id="codigo_patrimonial" name="codigo_patrimonial" value="{{ old('codigo_patrimonial') }}"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                            placeholder="Ej. 12001.23.04.0001">
                    </div>
                    <!-- Stock -->
                    <div>
                        <label for="stock" class="block mb-2 text-sm font-medium text-white/90">Stock inicial</label>
                        <input type="number" id="stock" name="stock" min="1" value="{{ old('stock', 1) }}"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            placeholder="Ej. 1">
                    </div>
                </div> <!-- Fin del grid -->

                <!-- Botones -->
                <div class="w-full flex items-right justify-end gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('items.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-[#D61C4E] to-[#b81742] hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(214,28,78,0.3)] hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Cancelar
                        </button>
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-[#1CD6CE] to-[#15b0aa] hover:opacity-90 text-[#293462] font-bold rounded-xl shadow-[0_0_15px_rgba(28,214,206,0.3)] hover:shadow-[0_0_25px_rgba(28,214,206,0.5)] transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Guardar Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // serializado => codigo obligatorio y stock fijo en 1; generico => sin codigo y stock libre
        (function () {
            const check = document.getElementById('tiene_codigo');
            const campoCodigo = document.getElementById('campo_codigo');
            const codigo = document.getElementById('codigo_patrimonial');
            const stock = document.getElementById('stock');

            function actualizar() {
                const serializado = check.checked;
                campoCodigo.classList.toggle('hidden', !serializado);
                codigo.required = serializado;
                if (serializado) {
                    stock.value = 1;
                }
                stock.disabled = serializado;
            }

            check.addEventListener('change', actualizar);
            actualizar();
        })();
    </script>
@endsection
