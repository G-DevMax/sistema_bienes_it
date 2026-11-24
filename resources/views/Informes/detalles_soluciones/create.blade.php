@extends('layouts.app')

@section('title', 'Crear Solución Común')

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('detalles-soluciones.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-400 to-cyan-300 bg-clip-text text-transparent">
                    Registrar Solución Común
                </h1>
                <p class="text-white/70 mt-1">Selecciona el tipo de problema y describe la solución frecuente</p>
            </div>
        </div>

        <div class="mb-4">
            @include('layouts.alerts')
        </div>

        @include('Informes.detalles_soluciones._form', [
            'action' => route('detalles-soluciones.store'),
            'method' => 'POST',
            'submitText' => 'Guardar Solución',
        ])
    </div>
@endsection
