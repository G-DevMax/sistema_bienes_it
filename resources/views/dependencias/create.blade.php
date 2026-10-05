@extends('layouts.app')

@section('title', 'Crear Dependencia')

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('dependencias.index') }}"
                class="p-2 text-white/50 hover:text-[#1CD6CE] hover:bg-[#1CD6CE]/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#1CD6CE] to-[#FEDB39] bg-clip-text text-transparent">
                    Registrar Dependencia
                </h1>
                <p class="text-white/70 mt-1">Completa el nombre para agregar un nuevo departamento</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('dependencias.store') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="nombre" class="block mb-2 text-sm font-medium text-white/90">Nombre de la Dependencia</label>
                    <input type="text" id="nombre" name="nombre"
                        class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                        placeholder="Ej. Recursos Humanos" required>
                </div>

                <!-- Botones -->
                <div class="w-full flex items-center justify-start gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('dependencias.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-[#D61C4E] to-[#b81742] hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(214,28,78,0.3)] hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-[#1CD6CE] to-[#15b0aa] hover:opacity-90 text-[#293462] font-bold rounded-xl shadow-[0_0_15px_rgba(28,214,206,0.3)] hover:shadow-[0_0_25px_rgba(28,214,206,0.5)] transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardar Dependencia
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
