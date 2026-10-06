@extends('layouts.app')

@section('title', 'Editar Item')

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('items.index') }}"
                class="p-2 text-white/50 hover:text-[#FEDB39] hover:bg-[#FEDB39]/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#FEDB39] to-[#D61C4E] bg-clip-text text-transparent">
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
            class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <!-- Reemplazar el route de action cuando el $item esté listo en backend -->
            <form action="{{ route('items.update', $item->id ?? 1) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Nombre -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="nombre" class="block mb-2 text-sm font-medium text-white/90">
                            Nombre
                        </label>
                        <input type="text" id="nombre" name="nombre" value="{{ $item->descripcion }}"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#FEDB39] focus:ring-2 focus:ring-[#FEDB39]/30 transition-all"
                            required>
                    </div>

                    <!-- Categoría -->
                    <div>
                        <label for="categoria" class="block mb-2 text-sm font-medium text-white/90">Categoría</label>
                        <select id="categoria" name="categoria"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#FEDB39] focus:ring-2 focus:ring-[#FEDB39]/30 transition-all appearance-none"
                            required>
                            @foreach ($categorias as $categoria)
                                @if ($categoria->id === $item->categoria_id)
                                    <option value="{{ $categoria->id }}" selected>{{ $categoria->nombre }}</option>
                                @else
                                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <!-- Codigo Patrimonial -->
                    <div>
                        <label for="codigo_patrimonial" class="block mb-2 text-sm font-medium text-white/90">Codigo
                            Patrimonial</label>
                        <input type="text" id="codigo_patrimonial" name="codigo_patrimonial"
                            value="{{ $item->codigo_patrimonial }}"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#FEDB39] focus:ring-2 focus:ring-[#FEDB39]/30 transition-all"
                            required>
                    </div>

                    <!-- Stock -->
                    <div>
                        <label for="stock" class="block mb-2 text-sm font-medium text-white/90">Stock</label>
                        <input type="number" id="stock" name="stock" value="{{ $item->stock }}"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#FEDB39] focus:ring-2 focus:ring-[#FEDB39]/30 transition-all"
                            required>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label for="estado" class="block mb-2 text-sm font-medium text-white/90">Estado</label>
                        <select id="estado" name="estado"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#FEDB39] focus:ring-2 focus:ring-[#FEDB39]/30 transition-all appearance-none"
                            required>
                            @foreach ($estados as $estado)
                                @if ($estado->id === 1 || $estado->id === 4)
                                    @if ($estado->id === $item->estado_id)
                                        <option value="{{ $estado->id }}" selected>{{ $estado->estado }}</option>
                                    @else
                                        <option value="{{ $estado->id }}">{{ $estado->estado }}</option>
                                    @endif
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex items-center justify-end gap-4 mt-8 pt-6 border-t border-white/10">
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
                        class="px-8 py-3 bg-gradient-to-r from-[#FEDB39] to-[#d6b520] hover:opacity-90 text-[#293462] font-bold rounded-xl shadow-[0_0_15px_rgba(254,219,57,0.3)] hover:shadow-[0_0_25px_rgba(254,219,57,0.5)] transition-all flex items-center gap-2">
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
