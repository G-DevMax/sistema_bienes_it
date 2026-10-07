@extends('layouts.app')

@section('title', 'Dashboard - Inicio de Sesión')

@section('content')
    <div class="w-full max-w-md animate-fade-in-up">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent mb-2">
                Sistema de Bienes IT</h1>
            <p class="text-white/70 text-lg">Tu sesion ha sido iniciada correctamente</p>
        </div>

        <div class="text-center mt-4">
            <button
                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-semibold rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-0.5 transition-all">
                <a href="/">Volver al Menú Principal</a>
            </button>
        </div>
    @endsection
