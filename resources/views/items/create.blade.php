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
        <div
            class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('items.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Nombre -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="nombre" class="block mb-2 text-sm font-medium text-white/90">Nombre /
                            Descripción</label>
                        <input type="text" id="nombre" name="nombre"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                            placeholder="Ej. Laptop ThinkPad T14" required>
                    </div>

                    <!-- Categoría -->
                    <div>
                        <label for="categoria" class="block mb-2 text-sm font-medium text-white/90">Categoría</label>
                        <select id="categoria" name="categoria"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            required>
                            <option disabled selected>Selecciona una categoría</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Estado -->
                    <div>
                        <label for="estado" class="block mb-2 text-sm font-medium text-white/90">Estado</label>
                        <select id="estado" name="estado"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            required>
                            @foreach ($estados as $estado)
                                @if ($estado->id === 1 || $estado->id === 4)
                                    <option value="{{ $estado->id }}">{{ $estado->estado }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="codigo">Código Patrimonial</label>
                        <input type="text" id="codigo" name="codigo"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            placeholder="Ej. 12001.23.04.0001" required>
                    </div>
                    <!-- Stock -->
                    <div>
                        <label for="stock">Stock</label>
                        <input type="number" id="stock" name="stock"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            placeholder="Ej. 1" required>
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
@endsection
