@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
    <div class="w-full max-w-6xl animate-fade-in-up">
        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Usuarios del Sistema
                </h1>
                <p class="text-white/70 mt-1">Administra los accesos y roles de los usuarios</p>
            </div>
            <a href="{{ route('usuarios.create') }}"
                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all gap-2 group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Usuario
            </a>
        </div>

        <!-- Mensaje de alerta dinamico -->
        <div>
            @include('layouts.alerts')
        </div>

        <!-- Tabla -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.3)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-center border-collapse">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-white">
                            <th class="px-6 py-4 font-semibold text-sm">ID</th>
                            <th class="px-6 py-4 font-semibold text-sm">Nombre</th>
                            <th class="px-6 py-4 font-semibold text-sm">Correo Electrónico</th>
                            <th class="px-6 py-4 font-semibold text-sm">Rol</th>
                            <th class="px-6 py-4 font-semibold text-sm">Estado</th>
                            <th class="px-6 py-4 font-semibold text-sm text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($usuarios as $usuario)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4 font-medium text-white/50">#{{ $usuario->id }}</td>
                                <td class="px-6 py-4">{{ $usuario->name }}</td>
                                <td class="px-6 py-4">{{ $usuario->email }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium bg-[#FEDB39]/20 text-[#FEDB39] border border-[#FEDB39]/30">
                                        {{ $usuario->rol->nombre ?? 'Desconocido' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium bg-[#FEDB39]/20 text-[#FEDB39] border border-[#FEDB39]/30">
                                        {{ $usuario->estado_user == 1 ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('usuarios.edit', $usuario->id) }}">
                                            <button
                                                class="p-2 text-white/60 hover:text-blue-400 hover:bg-blue-400/10 rounded-lg transition-colors"
                                                title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </button>
                                        </a>

                                        <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-white/60 hover:text-[#D61C4E] hover:bg-[#D61C4E]/10 rounded-lg transition-colors"
                                                title="Eliminar"
                                                onclick="return confirm('¿Estás seguro de desactivar este usuario?')">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-white/50">
                                    No hay usuarios registrados en el sistema.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($usuarios->hasPages())
                @include('layouts.pagination', ['paginator' => $usuarios])
            @endif
        </div>

        <!-- Botón para descargar registro de cambios -->
        <div class="mt-8 flex justify-end">
            <button onclick="document.getElementById('modalHistorial').classList.remove('hidden')"
                class="px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/20 rounded-xl text-white/80 hover:text-white transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Descargar registro de cambios en el SGDI
            </button>
        </div>

        <!-- Modal de Historial -->
        <div id="modalHistorial"
            class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
            <div class="bg-[#0b1329] border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl animate-fade-in-up">
                <h3 class="text-xl font-bold text-white mb-4">Descargar Historial de Cambios del SGDI</h3>
                <p class="text-sm text-white/60 mb-6">Seleccione el rango de fechas para descargar los registros en formato
                    JSON.</p>

                <form action="{{ route('historial.descargar') }}" method="GET">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-white/90 mb-1">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" required min="2026-01-01"
                                class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-xl text-white focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/90 mb-1">Fecha de Fin</label>
                            <input type="date" name="fecha_fin" required min="2026-01-01" value="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-xl text-white focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('modalHistorial').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl text-white/70 hover:bg-white/10 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            onclick="setTimeout(() => document.getElementById('modalHistorial').classList.add('hidden'), 500)"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 rounded-xl text-white font-medium shadow-lg shadow-blue-500/20 transition-all">
                            Descargar JSON
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
