@extends('layouts.app')

@section('title', 'Dashboard - Inicio de Sesión')

@section('content')
    <div class="w-full max-w-md animate-fade-in-up">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent mb-2">
                Sistema de Bienes IT</h1>
            <p class="text-white/70 text-lg">Ingresa tus credenciales para acceder al sistema</p>
        </div>

        <!-- Mensaje dinamico -->
        @include('layouts.alerts')

        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(59,130,246,0.1)]">
            <form action="#" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="email" class="block mb-2 text-sm font-medium text-white/90">Correo Electrónico</label>
                    <input type="email" id="email" name="email"
                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                        placeholder="nombre@ejemplo.com" required>
                </div>

                <div class="mb-6">
                    <label for="password" class="block mb-2 text-sm font-medium text-white/90">Contraseña</label>
                    <div class="relative">
                        <input type="password" id="password" name="password"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all pr-12"
                            placeholder="••••••••" required>
                        <button type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password'; this.firstElementChild.classList.toggle('hidden'); this.lastElementChild.classList.toggle('hidden');" class="absolute inset-y-0 right-0 flex items-center pr-3 text-white/50 hover:text-white/80 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <svg class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                    class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-0.5 transition-all">
                    Iniciar Sesión
                </button>
            </form>
        </div>
    </div>
    <div class="text-center mt-4">
        <button
            class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-0.5 transition-all">
            <a href="/">Volver al Menú Principal</a>
        </button>
    </div>
@endsection
