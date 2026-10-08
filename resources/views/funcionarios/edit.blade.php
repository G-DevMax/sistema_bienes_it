@extends('layouts.app')

@section('title', 'Editar Funcionario')

@section('content')
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('funcionarios.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Editar Funcionario
                </h1>
                <p class="text-white/70 mt-1">Actualiza los datos y la dependencia del personal</p>
            </div>
        </div>

        <!-- Formulario -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('funcionarios.update', $funcionarios->id ?? 1) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="col-span-1 md:col-span-2">
                        <label for="nombre" class="block mb-2 text-sm font-medium text-white/90">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" value="{{ $funcionarios->nombre }}"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            required>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label for="dependencia_id" class="block mb-2 text-sm font-medium text-white/90">Dependencia</label>
                        <select id="dependencia_id" name="dependencia_id"
                            class="searchable-select w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            @foreach ($dependencias as $dep)
                                <option value="{{ $dep->id }}"
                                    {{ $dep->id == $funcionarios->dependencia_id ? 'selected' : '' }}>
                                    {{ $dep->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-span-1 md:col-span-2">
                        <label for="estado_funcionario"
                            class="block mb-2 text-sm font-medium text-white/90">¿Activo?</label>
                        <select id="estado_funcionario" name="estado_funcionario"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            <option value="1" {{ $funcionarios->estado_funcionario == 1 ? 'selected' : '' }}>Sí
                            </option>
                            <option value="0" {{ $funcionarios->estado_funcionario == 0 ? 'selected' : '' }}>No
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Botones -->
                <div class="w-full flex items-center justify-start gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('funcionarios.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-bold rounded-xl shadow-blue-500/30 hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-bold rounded-xl shadow-blue-500/30 hover:shadow-blue-500/50 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        Actualizar Funcionario
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
