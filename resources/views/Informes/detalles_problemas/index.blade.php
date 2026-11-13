@extends('layouts.app')

@section('title', 'Problemas Comunes')

@section('content')
    <div class="w-full max-w-4xl animate-fade-in-up">
        <div class="flex items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Problemas Comunes
                </h1>
                <p class="text-white/70 mt-1">Catálogo de detalles generales por tipo de problema</p>
            </div>
            <a href="{{ route('informes.index') }}"
                class="px-5 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 border border-white/15 text-sm font-semibold text-white transition-all">
                Volver a Informes
            </a>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 text-center text-white/70">
            Apartado en construcción: aquí se administrarán los detalles predefinidos para los tipos de problema.
        </div>
    </div>
@endsection
