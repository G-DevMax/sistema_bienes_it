@extends('layouts.app')

@section('title', '403 - Acceso Denegado')

@section('content')
    <div class="w-full max-w-lg text-center animate-fade-in-up">
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 sm:p-10 shadow-[0_0_40px_rgba(244,63,94,0.15)]">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-rose-500/10 border border-rose-400/20 text-rose-400 mb-6 shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>

            <h1 class="text-7xl sm:text-8xl font-black tracking-tight bg-gradient-to-r from-rose-400 via-red-300 to-orange-300 bg-clip-text text-transparent mb-3">
                403
            </h1>

            <h2 class="text-2xl font-bold text-white mb-3">Acceso denegado</h2>

            <p class="text-white/70 text-sm sm:text-base mb-8 leading-relaxed">
                No tienes los permisos necesarios para acceder a esta sección o realizar esta acción.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="window.history.back()"
                    class="w-full sm:w-auto px-6 py-3 bg-white/5 hover:bg-white/10 border border-white/20 rounded-xl text-white font-medium transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Regresar
                </button>
                <a href="{{ auth()->check() ? route('menu_bientes') : url('/') }}"
                    class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 rounded-xl text-white font-semibold shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Ir al Inicio
                </a>
            </div>
        </div>
    </div>
@endsection
