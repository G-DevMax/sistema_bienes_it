@extends('layouts.app')

@section('title', 'Editar Item')

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('items.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Editar Item
                </h1>
                <p class="text-white/70 mt-1">Actualiza los datos del item registrado</p>
            </div>
        </div>
        <!-- mensaje de alerta -->
        <div>
            @include('layouts.alerts')
        </div>

        <!-- Formulario -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <!-- Reemplazar el route de action cuando el $item esté listo en backend -->
            <form action="{{ route('items.update', $item->id ?? 1) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Nombre -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="descripcion" class="block mb-2 text-sm font-medium text-white/90">
                            Nombre
                        </label>
                        <input type="text" id="descripcion" name="descripcion" value="{{ old('descripcion', $item->descripcion) }}"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            required>
                    </div>

                    <!-- Categoría -->
                    <div>
                        <label for="categoria_id" class="block mb-2 text-sm font-medium text-white/90">Categoría</label>
                        <select id="categoria_id" name="categoria_id"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" {{ old('categoria_id', $item->categoria_id) == $categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Codigo Patrimonial -->
                    <div>
                        <label for="codigo_patrimonial" class="block mb-2 text-sm font-medium text-white/90">Codigo
                            Patrimonial</label>
                        @if ($item->esSerializado())
                            <input type="text" id="codigo_patrimonial" name="codigo_patrimonial"
                                value="{{ old('codigo_patrimonial', $item->codigo_patrimonial) }}"
                                class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                                required>
                        @else
                            <input type="text" id="codigo_patrimonial" value="No aplica" disabled
                                class="w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl text-white/50 cursor-not-allowed">
                        @endif
                    </div>

                    <!-- Stock y estado (solo lectura) -->
                    <div class="col-span-1 md:col-span-2 p-4 rounded-xl border border-white/10 bg-white/5 flex flex-wrap items-center gap-6">
                        <div>
                            <span class="block text-xs text-white/50">Stock disponible / total</span>
                            <span class="text-lg font-bold text-[#1CD6CE]">{{ $item->stock_disponible }}</span>
                            <span class="text-white/50">/ {{ $item->stock_total }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-white/50">Estado</span>
                            <span class="text-white">{{ $item->estado->estado ?? '—' }}</span>
                        </div>
                        <p class="text-xs text-white/50 flex-1 min-w-[220px]">
                            El stock y el estado se actualizan automáticamente con los movimientos
                            (préstamos, transferencias, devoluciones, bajas e ingresos).
                        </p>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('items.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-bold rounded-xl shadow-blue-500/30 hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Cancelar
                        </button>
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-bold rounded-xl shadow-blue-500/30 hover:shadow-blue-500/50 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Actualizar Item
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
