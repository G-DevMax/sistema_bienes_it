@extends('layouts.app')

@section('title', 'Gestión de Movimientos')

@section('content')
    @php
        $badgesTipo = [
            'ingreso' => 'bg-green-500/20 text-green-400 border-green-500/30',
            'prestamo' => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
            'transferencia' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            'devolucion' => 'bg-blue-600/20 text-[#1CD6CE] border-[#1CD6CE]/30',
            'baja' => 'bg-[#D61C4E]/20 text-[#ff5c86] border-[#D61C4E]/30',
        ];
        $filtroTipo = request('tipo');
        $soloPendientes = request()->boolean('pendientes');
    @endphp
    <div class="w-full max-w-7xl animate-fade-in-up">
        <!-- Encabezado y Acción Principal -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Movimientos
                </h1>
                <p class="text-white/70 mt-1">Controla los préstamos, transferencias, devoluciones y bajas de bienes</p>
            </div>
            <a href="{{ route('movimientos.create') }}"
                class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(59,130,246,0.3)] hover:shadow-[0_0_25px_rgba(59,130,246,0.5)] transition-all gap-2 group">
                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nuevo Movimiento
            </a>
        </div>

        <div>
            @include('layouts.alerts')
        </div>
        <div class="mb-4">
            <x-barra_de_busqueda placeholder="Buscar por item o funcionario"></x-barra_de_busqueda>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <a href="{{ route('movimientos.index', array_filter(['pendientes' => $soloPendientes ? 1 : null])) }}"
                class="px-4 py-1.5 rounded-full text-sm border transition-all {{ !$filtroTipo ? 'bg-white/15 border-white/30 text-white' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">Todos</a>
            @foreach (\App\Models\movimientos::ETIQUETAS as $valor => $etiqueta)
                <a href="{{ route('movimientos.index', array_filter(['tipo' => $valor, 'pendientes' => $soloPendientes ? 1 : null])) }}"
                    class="px-4 py-1.5 rounded-full text-sm border transition-all {{ $filtroTipo === $valor ? 'bg-white/15 border-white/30 text-white' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">{{ $etiqueta }}</a>
            @endforeach
            <span class="flex-1"></span>
            <a href="{{ route('movimientos.index', array_filter(['tipo' => $filtroTipo, 'pendientes' => $soloPendientes ? null : 1])) }}"
                class="px-4 py-1.5 rounded-full text-sm border transition-all flex items-center gap-2 {{ $soloPendientes ? 'bg-[#FEDB39]/20 border-[#FEDB39]/40 text-[#FEDB39]' : 'border-white/10 text-white/60 hover:text-white hover:bg-white/5' }}">
                <span class="w-2 h-2 rounded-full {{ $soloPendientes ? 'bg-[#FEDB39]' : 'bg-white/30' }}"></span>
                Solo pendientes de devolución
            </a>
        </div>

        <!-- Tabla -->
        <div
            class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.3)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-blue-600/10 border-b border-white/10 text-[#1CD6CE]">
                            <th class="px-6 py-4 font-semibold">ID</th>
                            <th class="px-6 py-4 font-semibold">Item</th>
                            <th class="px-6 py-4 font-semibold">Funcionario</th>
                            <th class="px-6 py-4 font-semibold">Tipo</th>
                            <th class="px-6 py-4 font-semibold">Cantidad</th>
                            <th class="px-6 py-4 font-semibold">Fecha</th>
                            <th class="px-6 py-4 font-semibold">Situación</th>
                            <th class="px-6 py-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-white/90">
                        @forelse ($movimientos as $movimiento)
                            @php
                                $anulado = $movimiento->estaAnulado();
                                $pendiente = $movimiento->cantidadPendiente();
                            @endphp
                            <tr class="hover:bg-white/5 transition-colors group {{ $anulado ? 'opacity-50' : '' }}">
                                <td class="px-6 py-4 font-medium text-white/50">
                                    #{{ $movimiento->id }}
                                    @if ($movimiento->movimiento_origen_id)
                                        <span class="block text-xs text-white/40">de
                                            #{{ $movimiento->movimiento_origen_id }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="{{ $anulado ? 'line-through' : '' }}">{{ $movimiento->item->descripcion ?? 'Desconocido' }}</span>
                                    <span
                                        class="block text-xs text-white/50 font-mono">{{ $movimiento->item?->codigoMostrar() }}</span>
                                </td>
                                <td class="px-6 py-4">{{ $movimiento->funcionario->nombre ?? '— Informática —' }}</td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$movimiento->tipo_movimiento] ?? 'bg-white/10 border-white/20' }}">
                                        {{ $movimiento->etiquetaTipo() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-semibold">{{ $movimiento->cantidad }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $movimiento->fecha_movimiento->format('d/m/Y H:i') }}
                                    @if ($movimiento->fecha_devolucion_prevista)
                                        <span class="block text-xs text-white/50">Devolver:
                                            {{ $movimiento->fecha_devolucion_prevista->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($anulado)
                                        <span
                                            class="px-2 py-0.5 rounded-md text-xs bg-white/10 text-white/60 border border-white/20">Anulado</span>
                                    @elseif ($movimiento->esEntrega())
                                        @if ($pendiente === 0)
                                            <span
                                                class="px-2 py-0.5 rounded-md text-xs bg-green-500/15 text-green-400 border border-green-500/30">Cerrado</span>
                                        @elseif ($movimiento->tipo_movimiento === \App\Models\movimientos::TRANSFERENCIA)
                                            <span
                                                class="px-2 py-0.5 rounded-md text-xs bg-purple-500/20 text-purple-300 border border-purple-500/30">Transferido</span>
                                        @elseif ($movimiento->estaVencido())
                                            <span
                                                class="px-2 py-0.5 rounded-md text-xs bg-[#D61C4E]/20 text-[#ff5c86] border border-[#D61C4E]/40 animate-pulse">Vencido
                                                · {{ $pendiente }} pend.</span>
                                        @else
                                            <span
                                                class="px-2 py-0.5 rounded-md text-xs bg-[#FEDB39]/15 text-[#FEDB39] border border-[#FEDB39]/30">{{ $pendiente }}
                                                pendiente(s)</span>
                                        @endif
                                    @else
                                        <span class="text-white/40 text-xs">Registrado</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if (!$anulado && $pendiente > 0)
                                            <a href="{{ route('movimientos.show', $movimiento->id) }}#devolucion"
                                                class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-blue-600/20 text-[#1CD6CE] border border-[#1CD6CE]/30 hover:bg-blue-600/25 transition-colors">
                                                Devolver
                                            </a>
                                        @endif
                                        <a href="{{ route('movimientos.show', $movimiento->id) }}"
                                            class="p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                                            title="Ver detalle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                </path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-white/50">
                                    No hay movimientos registrados con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('layouts.pagination', ['paginator' => $movimientos])
        </div>
        <!-- Botón para descargar registro de cambios -->
        <div class="mt-8 flex justify-end">
            <button onclick="document.getElementById('modalInforme').classList.remove('hidden')"
                class="px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/20 rounded-xl text-white/80 hover:text-white transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Descargar Informe de Movimientos
            </button>
        </div>
        <!-- Modal de informe de movimientos -->
        <div id="modalInforme"
            class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center">
            <div class="bg-[#0b1329] border border-white/10 rounded-2xl p-6 w-full max-w-md shadow-2xl animate-fade-in-up">
                <h3 class="text-xl font-bold text-white mb-4">Descargar Informe de Movimientos</h3>
                <p class="text-sm text-white/60 mb-6">Seleccione el rango de fechas para descargar los registros en
                    formato Excel (.xlsx).</p>

                <form action="{{ route('movimientos.informe') }}" method="GET">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-white/90 mb-1">Fecha de Inicio</label>
                            <input type="date" name="fecha_desde" required min="2026-01-01"
                                class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-xl text-white focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white/90 mb-1">Fecha de Fin</label>
                            <input type="date" name="fecha_hasta" required min="2026-01-01"
                                value="{{ date('Y-m-d') }}"
                                class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-xl text-white focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                        </div>
                        <div>
                            <label for="tipo_movimiento" class="block text-sm font-medium text-white/90 mb-1">Tipo de
                                Movimiento</label>
                            <select name="tipo_movimiento" id="tipo_movimiento" required
                                class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-xl text-white focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                                <option value="" selected disabled>Seleccione el tipo de movimiento</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Prestamo">Prestamo</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('modalInforme').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl text-white/70 hover:bg-white/10 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            onclick="setTimeout(() => document.getElementById('modalInforme').classList.add('hidden'), 500)"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 rounded-xl text-white font-medium shadow-lg shadow-blue-500/20 transition-all">
                            Descargar Informe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
