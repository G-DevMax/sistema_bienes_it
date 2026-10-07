@extends('layouts.app')

@section('title', 'Gestión de Items')

@section('content')
    <div class="w-full animate-fade-in-up">
        <!-- Encabezado de la página -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4 w-full">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Inventario de Items
                </h1>
                <p class="text-white/70 mt-1">Gestiona el inventario de bienes del Departamento de IT</p>
            </div>
            <a href="{{ route('items.create') }}">
                <button
                    class="bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white px-5 py-2.5 rounded-xl font-bold shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6">
                        </path>
                    </svg>
                    Nuevo Item
                </button>
            </a>
        </div>

        <!-- Mensaje de alertas -->
        <div>
            @include('layouts.alerts')
        </div>

        <!-- Contenedor de la Tabla -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-white/80 whitespace-nowrap">
                    <thead class="bg-white/5 text-white uppercase text-xs font-semibold border-b border-white/10">
                        <tr>
                            <th scope="col" class="px-6 py-5 text-center">ID</th>
                            <th scope="col" class="px-6 py-5 text-center">Descripción</th>
                            <th scope="col" class="px-6 py-5 text-center">Categoría</th>
                            <th scope="col" class="px-6 py-5 text-center">Codigo Patrimonial</th>
                            <th scope="col" class="px-6 py-5 text-center" title="Disponible en Informática / Total">Stock
                            </th>
                            <th scope="col" class="px-6 py-5 text-center">Estado</th>
                            <th scope="col" class="px-6 py-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-center">
                        @forelse ($items as $item)
                            @php $r = $resumen[$item->id] ?? ['prestados' => 0, 'transferidos' => 0, 'inservibles' => 0]; @endphp
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4 font-medium text-white/50">
                                    #{{ $item->id }}
                                </td>
                                <td class="px-6 py-4 text-white font-medium">
                                    {{ $item->descripcion }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-white border border-white/20">
                                        {{ $item->categoria->nombre ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-white">
                                    @if ($item->esSerializado())
                                        <span class="font-mono">{{ $item->codigo_patrimonial }}</span>
                                    @else
                                        <span
                                            class="px-2 py-0.5 rounded-md text-xs bg-white/5 text-white/50 border border-white/10">No
                                            aplica</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-white">
                                    <span
                                        class="font-bold text-lg {{ $item->stock_disponible > 0 ? 'text-[#1CD6CE]' : 'text-[#D61C4E]' }}">{{ $item->stock_disponible }}</span>
                                    <span class="text-white/40">/ {{ $item->stock_total }}</span>
                                </td>
                                <td class="px-6 py-4 text-white">
                                    @if ($item->esSerializado())
                                        @php
                                            $badge = match ($item->estado_id) {
                                                1 => 'bg-green-500/20 text-green-400 border-green-500/40',
                                                2 => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
                                                3 => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/40',
                                                4 => 'bg-red-500/20 text-red-400 border-red-500/40',
                                                default => 'bg-gray-500/20 text-gray-300 border-gray-500/40',
                                            };
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-xs font-medium border {{ $badge }}">
                                            {{ $item->estado->estado ?? 'Estado Desconocido' }}
                                        </span>
                                    @else
                                        {{-- items genericos: desglose por estado --}}
                                        <div class="flex flex-wrap justify-center gap-1 text-xs">
                                            <span
                                                class="px-2 py-0.5 rounded-full bg-green-500/20 text-green-400 border border-green-500/30"
                                                title="Disponibles">{{ $item->stock_disponible }} disp.</span>
                                            @if ($r['prestados'] > 0)
                                                <span
                                                    class="px-2 py-0.5 rounded-full bg-yellow-500/20 text-yellow-300 border border-yellow-500/30"
                                                    title="Prestados">{{ $r['prestados'] }} prest.</span>
                                            @endif
                                            @if ($r['transferidos'] > 0)
                                                <span
                                                    class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30"
                                                    title="Transferidos">{{ $r['transferidos'] }} transf.</span>
                                            @endif
                                            @if ($r['inservibles'] > 0)
                                                <span
                                                    class="px-2 py-0.5 rounded-full bg-red-500/20 text-red-400 border border-red-500/30"
                                                    title="Dados de baja">{{ $r['inservibles'] }} baja</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                                        @if ($item->stock_disponible > 0)
                                            <a href="{{ route('movimientos.create', ['item_id' => $item->id]) }}"
                                                class="p-2 text-white/60 hover:text-blue-400 hover:bg-blue-400/10 rounded-lg transition-colors"
                                                title="Prestar / Transferir">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        @unless ($item->esSerializado())
                                            {{-- ingreso de stock para items genericos --}}
                                            <details class="relative">
                                                <summary
                                                    class="list-none p-2 text-white/60 hover:text-green-400 hover:bg-green-500/10 rounded-lg transition-colors cursor-pointer"
                                                    title="Ingresar stock">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                </summary>
                                                <form action="{{ route('items.ingresar-stock', $item->id) }}" method="POST"
                                                    class="absolute right-0 top-full mt-2 z-20 w-64 p-4 bg-slate-900 border border-white/15 rounded-xl shadow-2xl text-left space-y-3">
                                                    @csrf
                                                    <p class="text-xs text-white/60">Ingresar unidades de <b
                                                            class="text-white">{{ $item->descripcion }}</b></p>
                                                    <input type="number" name="cantidad" min="1" value="1" required
                                                        class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white focus:outline-none focus:border-blue-400">
                                                    <input type="text" name="observaciones"
                                                        placeholder="Observación (opcional)"
                                                        class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-blue-400">
                                                    <button type="submit"
                                                        class="w-full py-2 rounded-lg bg-blue-600 text-white font-bold hover:opacity-90">Ingresar</button>
                                                </form>
                                            </details>
                                        @endunless

                                        <a href="{{ route('items.edit', $item->id) }}"
                                            class="p-2 text-white/60 hover:text-blue-400 hover:bg-blue-400/10 rounded-lg transition-colors"
                                            title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                        </a>

                                        @if ($item->stock_disponible > 0)
                                            <a href="{{ route('movimientos.create', ['tipo' => 'baja', 'item_id' => $item->id]) }}"
                                                class="p-2 text-white/60 hover:text-[#D61C4E] hover:bg-[#D61C4E]/10 rounded-lg transition-colors"
                                                title="Dar de baja">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-white/50">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 mb-4 text-white/30" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                            </path>
                                        </svg>
                                        <p class="text-lg">No hay items registrados</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @include('layouts.pagination', ['paginator' => $items])
        </div>
        <div class="w-full max-w-6xl animate-fade-in-up flex justify-center items-center">
            <!-- Acciones secundarias -->
            <x-botones_secundarios href="{{ route('categorias.index') }}">
                Ir a Categorias
            </x-botones_secundarios>
        </div>
    </div>
@endsection
