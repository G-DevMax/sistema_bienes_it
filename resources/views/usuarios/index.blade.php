@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
    <div class="w-full max-w-6xl animate-fade-in-up">
        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#1CD6CE] to-[#FEDB39] bg-clip-text text-transparent">
                    Usuarios del Sistema
                </h1>
                <p class="text-white/70 mt-1">Administra los accesos y roles de los usuarios</p>
            </div>
            <a href="{{ route('usuarios.create') }}"
                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-[#1CD6CE] to-[#15b0aa] hover:opacity-90 text-[#293462] font-bold rounded-xl shadow-[0_0_15px_rgba(28,214,206,0.3)] hover:shadow-[0_0_25px_rgba(28,214,206,0.5)] transition-all gap-2 group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Usuario
            </a>
        </div>

        <!-- Tabla -->
        <div
            class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.3)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-center border-collapse">
                    <thead>
                        <tr class="bg-[#1CD6CE]/10 border-b border-white/10 text-white">
                            <th class="px-6 py-4 font-semibold text-sm">ID</th>
                            <th class="px-6 py-4 font-semibold text-sm">Nombre</th>
                            <th class="px-6 py-4 font-semibold text-sm">Correo Electrónico</th>
                            <th class="px-6 py-4 font-semibold text-sm">Rol</th>
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
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="{{ route('usuarios.edit', $usuario->id) }}">
                                            <button
                                                class="p-2 text-white/60 hover:text-[#FEDB39] hover:bg-[#FEDB39]/10 rounded-lg transition-colors"
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
                                                onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
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
        </div>
    </div>
@endsection
