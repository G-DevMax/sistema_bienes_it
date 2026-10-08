@extends('layouts.app')

@section('title', 'Editar Usuario')

@section('content')
    @php
        // Mock data para previsualización
        if (!isset($usuario)) {
            $usuario = (object) [
                'id' => 1,
                'name' => 'Admin Sistema',
                'email' => 'admin@ejemplo.com',
                'rol_id' => 1,
            ];
        }
    @endphp
    <div class="w-full max-w-3xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('usuarios.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Editar Usuario
                </h1>
                <p class="text-white/70 mt-1">Modifica los datos y permisos del usuario</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('usuarios.update', $usuario->id ?? 1) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Nombre -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="name" class="block mb-2 text-sm font-medium text-white/90">Nombre Completo</label>
                        <input type="text" id="name" name="name" value="{{ $usuario->name }}"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            required>
                    </div>

                    <!-- Correo -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="email" class="block mb-2 text-sm font-medium text-white/90">Correo
                            Electrónico</label>
                        <input type="email" id="email" name="email" value="{{ $usuario->email }}"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            required>
                    </div>

                    <!-- Contraseña (Opcional) -->
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-white/90">Nueva Contraseña <span
                                class="text-white/50 text-xs font-normal">(Opcional)</span></label>
                        <input type="password" id="password" name="password"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            placeholder="Dejar en blanco para no cambiar">
                    </div>

                    <!-- Rol -->
                    <div>
                        <label for="rol_id" class="block mb-2 text-sm font-medium text-white/90">Rol del Usuario</label>
                        <select id="rol_id" name="rol_id"
                            class="searchable-select w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            @if (isset($roles))
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->id }}"
                                        {{ $rol->id == $usuario->rol_id ? 'selected' : '' }}>
                                        {{ $rol->nombre }}
                                    </option>
                                @endforeach
                            @else
                                <option value="1" {{ $usuario->rol_id == 1 ? 'selected' : '' }}>Administrador</option>
                                <option value="2" {{ $usuario->rol_id == 2 ? 'selected' : '' }}>Usuario Standard
                                </option>
                            @endif
                        </select>
                    </div>
                    <!-- Estado del Usuario (activo o desactivado) -->
                    <div>
                        <label for="estado_user" class="block mb-2 text-sm font-medium text-white/90">
                            Estado del Usuario
                        </label>
                        <select id="estado_user" name="estado_user"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            <option value="1" {{ $usuario->estado_user == 1 ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ $usuario->estado_user == 0 ? 'selected' : '' }}>Desactivado</option>
                        </select>
                    </div>
                </div> <!-- Fin del grid -->

                <!-- Botones -->
                <div class="w-full flex items-center justify-end gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('usuarios.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:opacity-90 text-white font-bold rounded-xl shadow-blue-500/30 hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
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
                        Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
