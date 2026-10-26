@extends('layouts.app')

@section('title', '503 - Sistema en Mantenimiento')

@section('content')
    <div class="w-full max-w-lg text-center animate-fade-in-up">
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 sm:p-10 shadow-[0_0_40px_rgba(245,158,11,0.15)]">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-amber-500/10 border border-amber-400/20 text-amber-400 mb-6 shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>

            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/25 text-amber-300 text-xs font-semibold mb-4">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                Mantenimiento en curso
            </div>

            <h1 class="text-7xl sm:text-8xl font-black tracking-tight bg-gradient-to-r from-amber-400 via-yellow-300 to-orange-400 bg-clip-text text-transparent mb-3">
                503
            </h1>

            <h2 class="text-2xl font-bold text-white mb-3">Sistema en Mantenimiento</h2>
            
            <p class="text-white/70 text-sm sm:text-base mb-8 leading-relaxed">
                Estamos realizando tareas de optimización y mejoras programadas en el servidor. El servicio se restablecerá en breve.
            </p>

            <div class="flex justify-center">
                <button type="button" onclick="window.location.reload()"
                    class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 rounded-xl text-white font-semibold shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reintentar conexión
                </button>
            </div>
        </div>
    </div>
@endsection
