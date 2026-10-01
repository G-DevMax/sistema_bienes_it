@extends('layouts.app')

@section('title', 'Gestión de Items')

@section('content')
<div class="w-full animate-fade-in-up">
    <!-- Encabezado de la página -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4 w-full">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-[#1CD6CE] to-[#FEDB39] bg-clip-text text-transparent">
                Inventario de Items
            </h1>
            <p class="text-white/70 mt-1">Gestiona todos los bienes y equipos del sistema</p>
        </div>
        <button class="bg-gradient-to-r from-[#1CD6CE] to-[#15b0aa] hover:opacity-90 text-[#293462] px-5 py-2.5 rounded-xl font-bold shadow-[0_0_15px_rgba(28,214,206,0.3)] hover:shadow-[0_0_25px_rgba(28,214,206,0.5)] transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Nuevo Item
        </button>
    </div>

    <!-- Contenedor de la Tabla -->
    <div class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl overflow-hidden shadow-[0_0_40px_rgba(0,0,0,0.3)]">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-white/80 whitespace-nowrap">
                <thead class="bg-white/5 text-white uppercase text-xs font-semibold border-b border-white/10">
                    <tr>
                        <th scope="col" class="px-6 py-5">ID</th>
                        <th scope="col" class="px-6 py-5">Nombre / Descripción</th>
                        <th scope="col" class="px-6 py-5">Categoría</th>
                        <th scope="col" class="px-6 py-5">Estado</th>
                        <th scope="col" class="px-6 py-5">Fecha de Ingreso</th>
                        <th scope="col" class="px-6 py-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @php
                        $dummyItems = $items ?? [
                            (object)['id' => 1, 'nombre' => 'Laptop ThinkPad T14', 'categoria' => 'Equipos de Cómputo', 'estado' => 'Activo', 'fecha' => '2023-10-15'],
                            (object)['id' => 2, 'nombre' => 'Monitor Dell 27"', 'categoria' => 'Periféricos', 'estado' => 'En Reparación', 'fecha' => '2023-11-02'],
                            (object)['id' => 3, 'nombre' => 'Silla Ergonómica Herman Miller', 'categoria' => 'Mobiliario', 'estado' => 'Activo', 'fecha' => '2024-01-20'],
                        ];
                    @endphp

                    @forelse ($dummyItems as $item)
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4 font-medium text-white/50">#{{ str_pad($item->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 text-white font-medium">{{ $item->nombre }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium bg-white/10 text-white border border-white/20">
                                    {{ $item->categoria }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if(strtolower($item->estado) == 'activo')
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#1CD6CE]/20 text-[#1CD6CE] border border-[#1CD6CE]/30 flex items-center w-fit gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#1CD6CE] shadow-[0_0_5px_#1CD6CE]"></span>
                                        {{ $item->estado }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#FEDB39]/20 text-[#FEDB39] border border-[#FEDB39]/30 flex items-center w-fit gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#FEDB39] shadow-[0_0_5px_#FEDB39]"></span>
                                        {{ $item->estado }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-white/60">{{ $item->fecha }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button class="p-2 text-white/60 hover:text-[#1CD6CE] hover:bg-[#1CD6CE]/10 rounded-lg transition-colors" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button class="p-2 text-white/60 hover:text-[#D61C4E] hover:bg-[#D61C4E]/10 rounded-lg transition-colors" title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-white/50">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 mb-4 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <p class="text-lg">No hay items registrados</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        <div class="px-6 py-4 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-sm text-white/60">Mostrando <span class="text-white font-bold">1</span> a <span class="text-white font-bold">3</span> de <span class="text-white font-bold">3</span> resultados</span>
            <div class="flex gap-1">
                <button class="px-3 py-1 rounded-md bg-white/5 text-white/40 cursor-not-allowed text-sm">Anterior</button>
                <button class="px-3 py-1 rounded-md bg-[#1CD6CE] text-[#293462] font-bold text-sm">1</button>
                <button class="px-3 py-1 rounded-md bg-white/5 text-white/40 cursor-not-allowed text-sm">Siguiente</button>
            </div>
        </div>
    </div>
</div>
@endsection
