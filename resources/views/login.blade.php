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
                    <input type="password" id="password" name="password"
                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                        placeholder="••••••••" required>
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
