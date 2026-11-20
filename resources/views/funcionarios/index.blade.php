@extends('layouts.app')

@section('title', 'Gestión de Funcionarios')

@section('content')
    <div class="w-full max-w-6xl animate-fade-in-up">
        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Funcionarios
                </h1>
                <p class="text-white/70 mt-1">Administra al personal y su respectiva dependencia</p>
            </div>
            @can('funcionarios.gestionar')
                <a href="{{ route('funcionarios.create') }}"
                    class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all gap-2 group">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nuevo Funcionario
                </a>
            @endcan
        </div>
        <!-- mensaje dinamico -->
        <div>
            @include('layouts.alerts')
        </div>

        <!-- barra de busqueda -->
        <div class="mb-4">
            <x-barra_de_busqueda placeholder="Buscar Funcionario por nombre..."></x-barra_de_busqueda>
        </div>

        <!-- Tabla -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.3)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-white">
                            <th class="px-6 py-4 font-semibold text-sm">ID</th>
                            <th class="px-6 py-4 font-semibold text-sm">Nombre del Funcionario</th>
                            <th class="px-6 py-4 font-semibold text-sm">Dependencia</th>
                            <th class="px-6 py-4 font-semibold text-sm">¿Activo?</th>
                            <th class="px-6 py-4 font-semibold text-sm text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($funcionarios as $funcionario)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4 font-medium text-white/50">#{{ $funcionario->id }}</td>
                                <td class="px-6 py-4">{{ $funcionario->nombre }}</td>
                                <!-- dependencia del funcionario -->
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium bg-blue-600/20 text-white border border-[#1CD6CE]/30">
                                        {{ $funcionario->dependencia->nombre ?? 'Sin Asignar' }}
                                    </span>
                                </td>
                                <!-- activo o inactivo -->
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium {{ $funcionario->estado_funcionario == 1 ? 'bg-green-500/20 text-green-500 border border-green-500/30' : 'bg-red-500/20 text-red-500 border border-red-500/30' }}">
                                        {{ $funcionario->estado_funcionario == 1 ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <!-- acciones -->
                                <td class="px-6 py-4 text-right">
                                    @can('funcionarios.gestionar')
                                        <div
                                            class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ route('funcionarios.edit', $funcionario->id) }}">
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
                                            @if ($funcionario->tiene_pendientes)
                                                <button type="button"
                                                    onclick="document.getElementById('modalAdvertenciaPendientes').classList.remove('hidden')"
                                                    class="p-2 text-white/60 hover:text-amber-400 hover:bg-amber-400/10 rounded-lg transition-colors"
                                                    title="Dar de baja">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            @else
                                                <form action="{{ route('funcionarios.destroy', $funcionario->id) }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-2 text-white/60 hover:text-[#D61C4E] hover:bg-[#D61C4E]/10 rounded-lg transition-colors"
                                                        title="Eliminar"
                                                        onclick="return confirm('¿Estás seguro de dar de baja este funcionario?')">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-white/50">
                                    No hay funcionarios registrados en el sistema.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($funcionarios->hasPages())
                @include('layouts.pagination', ['paginator' => $funcionarios])
            @endif
        </div>
        <div class="w-full max-w-6xl animate-fade-in-up flex justify-center items-center">
            <!-- Acciones secundarias -->
            <x-botones_secundarios href="{{ route('dependencias.index') }}">
                Ir a Dependencias
            </x-botones_secundarios>
        </div>

        <!-- Modal de advertencia por préstamos o transferencias pendientes -->
        <div id="modalAdvertenciaPendientes"
            class="fixed inset-0 z-50 {{ session()->has('advertencia_pendientes') ? '' : 'hidden' }} bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-[#0b1329] border border-amber-500/40 rounded-2xl p-6 w-full max-w-md shadow-2xl animate-fade-in-up">
                <div class="flex items-start gap-3 mb-4">
                    <div class="p-2 rounded-xl bg-amber-500/20 text-amber-400 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86l-7.4 12.82A1.75 1.75 0 004.41 19.3h14.8a1.75 1.75 0 001.52-2.62l-7.4-12.82a1.75 1.75 0 00-3.04 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">No se puede desactivar al funcionario</h3>
                        <p class="text-sm text-white/80">
                            {{ session('advertencia_pendientes', \App\Models\funcionarios::MENSAJE_PENDIENTES) }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button"
                        onclick="document.getElementById('modalAdvertenciaPendientes').classList.add('hidden')"
                        class="px-4 py-2 bg-amber-500/20 hover:bg-amber-500/30 border border-amber-400/40 rounded-xl text-amber-200 font-medium transition-colors">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    @endsection
