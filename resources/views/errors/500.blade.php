@extends('layouts.app')

@section('title', '500 - Error Interno del Servidor')

@section('content')
    <div class="w-full max-w-lg text-center animate-fade-in-up">
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 sm:p-10 shadow-[0_0_40px_rgba(239,68,68,0.15)]">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-400 mb-6 shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-7xl sm:text-8xl font-black tracking-tight bg-gradient-to-r from-red-400 via-rose-300 to-amber-300 bg-clip-text text-transparent mb-3">
                500
            </h1>

            <h2 class="text-2xl font-bold text-white mb-3">Error interno del servidor</h2>
            
            <p class="text-white/70 text-sm sm:text-base mb-8 leading-relaxed">
                Ocurrió un problema inesperado al procesar tu solicitud. Intenta recargar o regresa al panel principal.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button type="button" onclick="window.location.reload()"
                    class="w-full sm:w-auto px-6 py-3 bg-white/5 hover:bg-white/10 border border-white/20 rounded-xl text-white font-medium transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Recargar
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
