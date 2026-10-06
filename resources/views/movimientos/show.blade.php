@extends('layouts.app')

@section('title', 'Detalle de Movimiento')

@section('content')
@php
    $anulado = $movimiento->estaAnulado();
    $pendiente = $movimiento->cantidadPendiente();
    
    $badgesTipo = [
        'ingreso' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'prestamo' => 'bg-[#FEDB39]/20 text-[#FEDB39] border-[#FEDB39]/30',
        'transferencia' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
        'devolucion' => 'bg-[#1CD6CE]/20 text-[#1CD6CE] border-[#1CD6CE]/30',
        'baja' => 'bg-[#D61C4E]/20 text-[#ff5c86] border-[#D61C4E]/30',
    ];
@endphp
<div class="w-full max-w-5xl animate-fade-in-up">
    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('movimientos.index') }}"
                class="p-2 text-white/50 hover:text-white hover:bg-white/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-bold text-white">
                        Movimiento #{{ $movimiento->id }}
                    </h1>
                    <span class="px-3 py-1 rounded-full text-xs font-medium border {{ $badgesTipo[$movimiento->tipo_movimiento] ?? 'bg-white/10 border-white/20' }}">
                        {{ $movimiento->etiquetaTipo() }}
                    </span>
                    @if ($anulado)
                        <span class="px-3 py-1 rounded-full text-xs font-medium border bg-white/10 text-white/60 border-white/20">Anulado</span>
                    @endif
                </div>
                <p class="text-white/60 mt-1">Registrado el {{ $movimiento->fecha_movimiento->format('d/m/Y \a \l\a\s H:i') }} por {{ $movimiento->usuario->name ?? 'Sistema' }}</p>
            </div>
        </div>
        
        @if (!$anulado)
            <button onclick="document.getElementById('modal-anular').showModal()"
                class="px-4 py-2 rounded-xl text-sm font-semibold border border-white/20 text-white/70 hover:bg-white/10 hover:text-white transition-all">
                Anular Movimiento
            </button>
        @endif
    </div>

    <div>
        @include('layouts.alerts')
    </div>

    @if ($anulado)
        <div class="mb-6 p-4 rounded-xl bg-white/5 border border-white/10 flex items-start gap-3">
            <svg class="w-6 h-6 text-white/40 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
                <h4 class="font-bold text-white/80">Movimiento Anulado</h4>
                <p class="text-sm text-white/60 mt-1">
                    Anulado el {{ $movimiento->anulado_at->format('d/m/Y H:i') }} por {{ $movimiento->anuladoPor->name ?? 'Sistema' }}.
                </p>
                <p class="text-sm text-white/80 mt-2 bg-[#293462]/50 p-3 rounded-lg">Motivo: {{ $movimiento->motivo_anulacion }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Detalles del Movimiento (Columna izquierda) -->
        <div class="md:col-span-2 space-y-6">
            <!-- Info principal -->
            <div class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-6 shadow-lg">
                <h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-2">Información General</h3>
                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                    <div>
                        <span class="block text-xs font-medium text-white/50 mb-1">Item</span>
                        <div class="font-medium text-white">
                            <a href="{{ route('items.edit', $movimiento->item_id) }}" class="hover:text-[#1CD6CE] transition-colors">
                                {{ $movimiento->item->descripcion }}
                            </a>
                            <span class="block text-sm text-white/50 font-mono">{{ $movimiento->item->codigoMostrar() }}</span>
                        </div>
                    </div>
                    
                    @if ($movimiento->funcionario)
                        <div>
                            <span class="block text-xs font-medium text-white/50 mb-1">Funcionario Receptor</span>
                            <div class="font-medium text-white">{{ $movimiento->funcionario->nombre }}</div>
                        </div>
                    @endif
                    
                    <div>
                        <span class="block text-xs font-medium text-white/50 mb-1">Cantidad Movidą</span>
                        <div class="font-bold text-xl text-white">{{ $movimiento->cantidad }} <span class="text-sm font-normal text-white/60">unidad(es)</span></div>
                    </div>
                    
                    @if ($movimiento->tipo_movimiento === 'prestamo' && $movimiento->fecha_devolucion_prevista)
                        <div>
                            <span class="block text-xs font-medium text-white/50 mb-1">Devolución Prevista</span>
                            <div class="font-medium {{ $movimiento->estaVencido() ? 'text-[#ff5c86]' : 'text-white' }}">
                                {{ $movimiento->fecha_devolucion_prevista->format('d/m/Y') }}
                                @if ($movimiento->estaVencido())
                                    <span class="text-xs ml-1">(Vencido)</span>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if ($movimiento->movimiento_origen_id)
                        <div>
                            <span class="block text-xs font-medium text-white/50 mb-1">Movimiento de Origen</span>
                            <div class="font-medium text-[#1CD6CE]">
                                <a href="{{ route('movimientos.show', $movimiento->movimiento_origen_id) }}" class="hover:underline">
                                    Ver #{{ $movimiento->movimiento_origen_id }} ({{ $movimiento->origen->etiquetaTipo() }})
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($movimiento->motivo)
                    <div class="mt-6 pt-4 border-t border-white/10">
                        <span class="block text-xs font-medium text-white/50 mb-1">Motivo</span>
                        <p class="text-sm text-white/90">{{ $movimiento->motivo }}</p>
                    </div>
                @endif
                @if ($movimiento->observaciones)
                    <div class="mt-4 pt-4 border-t border-white/10">
                        <span class="block text-xs font-medium text-white/50 mb-1">Observaciones / Notas</span>
                        <p class="text-sm text-white/80 bg-white/5 p-3 rounded-lg">{{ $movimiento->observaciones }}</p>
                    </div>
                @endif
            </div>

            <!-- Hijos (devoluciones/bajas derivadas de esta entrega) -->
            @if ($movimiento->esEntrega() && $movimiento->hijos->isNotEmpty())
                <div class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-white mb-4 border-b border-white/10 pb-2">Historial de esta entrega</h3>
                    <div class="space-y-3">
                        @foreach ($movimiento->hijos as $hijo)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/10 {{ $hijo->estaAnulado() ? 'opacity-50' : '' }}">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-white">#{{ $hijo->id }}</span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border {{ $badgesTipo[$hijo->tipo_movimiento] ?? '' }}">
                                            {{ $hijo->etiquetaTipo() }}
                                        </span>
                                        @if ($hijo->estaAnulado())
                                            <span class="text-xs text-white/50">(Anulado)</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-white/60 mt-1">{{ $hijo->fecha_movimiento->format('d/m/Y H:i') }} por {{ $hijo->usuario->name ?? '?' }}</p>
                                </div>
                                <div class="text-right flex items-center gap-4">
                                    <span class="font-bold text-white">{{ $hijo->cantidad }} unid.</span>
                                    <a href="{{ route('movimientos.show', $hijo->id) }}" class="text-white/40 hover:text-white transition-colors" title="Ver detalle">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Acciones sobre entregas pendientes (Columna derecha) -->
        <div class="md:col-span-1">
            @if ($movimiento->esEntrega() && !$anulado)
                <div class="bg-[#293462]/60 backdrop-blur-xl border border-[#1CD6CE]/30 rounded-2xl p-6 shadow-[0_0_30px_rgba(28,214,206,0.1)] sticky top-24">
                    <h3 class="text-lg font-bold text-white mb-2">Situación Actual</h3>
                    
                    <div class="mb-6">
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold {{ $pendiente > 0 ? 'text-[#1CD6CE]' : 'text-green-400' }}">{{ $pendiente }}</span>
                            <span class="text-sm text-white/60">de {{ $movimiento->cantidad }} pendientes</span>
                        </div>
                        @if ($pendiente === 0)
                            <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-green-500/20 text-green-400 text-sm font-medium border border-green-500/30">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Movimiento Cerrado
                            </div>
                        @endif
                    </div>

                    @if ($pendiente > 0)
                        <div class="space-y-4 pt-4 border-t border-white/10" id="devolucion">
                            <!-- Accion: Devolver -->
                            <details class="group/accion">
                                <summary class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10 hover:border-[#1CD6CE]/50 hover:bg-[#1CD6CE]/10 transition-colors cursor-pointer list-none">
                                    <div class="p-2 rounded-lg bg-[#1CD6CE]/20 text-[#1CD6CE]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <span class="block font-bold text-white">Registrar Devolución</span>
                                        <span class="block text-xs text-white/60">Volver a ingresar al stock</span>
                                    </div>
                                    <svg class="w-4 h-4 text-white/40 group-open/accion:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </summary>
                                <div class="mt-3 p-4 rounded-xl bg-black/20 border border-white/5">
                                    <form action="{{ route('movimientos.devolver', $movimiento->id) }}" method="POST">
                                        @csrf
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-xs font-medium text-white/70 mb-1">Cantidad a devolver</label>
                                                <input type="number" name="cantidad" min="1" max="{{ $pendiente }}" value="{{ $pendiente }}" required
                                                    class="w-full px-3 py-2 bg-[#293462] border border-white/20 rounded-lg text-white focus:outline-none focus:border-[#1CD6CE]"
                                                    {{ $movimiento->item->esSerializado() ? 'readonly' : '' }}>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-white/70 mb-1">Fecha de devolución</label>
                                                <input type="datetime-local" name="fecha_movimiento" value="{{ now()->format('Y-m-d\TH:i') }}" required
                                                    class="w-full px-3 py-2 bg-[#293462] border border-white/20 rounded-lg text-white focus:outline-none focus:border-[#1CD6CE]">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-white/70 mb-1">Observaciones (Opcional)</label>
                                                <input type="text" name="observaciones" placeholder="Ej. Devuelve 1 cargador roto"
                                                    class="w-full px-3 py-2 bg-[#293462] border border-white/20 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE]">
                                            </div>
                                            <button type="submit" class="w-full py-2.5 rounded-lg bg-[#1CD6CE] text-[#293462] font-bold hover:opacity-90 transition-opacity mt-2">
                                                Confirmar Devolución
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>

                            <!-- Accion: Baja (pérdida) -->
                            <details class="group/accion">
                                <summary class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/10 hover:border-[#D61C4E]/50 hover:bg-[#D61C4E]/10 transition-colors cursor-pointer list-none">
                                    <div class="p-2 rounded-lg bg-[#D61C4E]/20 text-[#ff5c86]">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </div>
                                    <div class="flex-1">
                                        <span class="block font-bold text-white">Dar de Baja</span>
                                        <span class="block text-xs text-white/60">Pérdida o daño total</span>
                                    </div>
                                    <svg class="w-4 h-4 text-white/40 group-open/accion:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </summary>
                                <div class="mt-3 p-4 rounded-xl bg-black/20 border border-white/5">
                                    <form action="{{ route('movimientos.baja', $movimiento->id) }}" method="POST">
                                        @csrf
                                        <div class="space-y-3">
                                            <div class="flex gap-3">
                                                <div class="flex-1">
                                                    <label class="block text-xs font-medium text-white/70 mb-1">Cantidad</label>
                                                    <input type="number" name="cantidad" min="1" max="{{ $pendiente }}" value="{{ $pendiente }}" required
                                                        class="w-full px-3 py-2 bg-[#293462] border border-white/20 rounded-lg text-white focus:outline-none focus:border-[#D61C4E]"
                                                        {{ $movimiento->item->esSerializado() ? 'readonly' : '' }}>
                                                </div>
                                                <div class="flex-1">
                                                    <label class="block text-xs font-medium text-white/70 mb-1">Fecha</label>
                                                    <input type="date" name="fecha_movimiento" value="{{ now()->format('Y-m-d') }}" required
                                                        class="w-full px-3 py-2 bg-[#293462] border border-white/20 rounded-lg text-white focus:outline-none focus:border-[#D61C4E]">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-white/70 mb-1">Motivo Corto</label>
                                                <input type="text" name="motivo" placeholder="Ej. Robo, Extravío..." required
                                                    class="w-full px-3 py-2 bg-[#293462] border border-white/20 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-[#D61C4E]">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-[#ff5c86] mb-1">Nota de Responsabilidad (Req.)</label>
                                                <textarea name="observaciones" rows="2" placeholder="Detallar cómo se perdió y la responsabilidad del funcionario..." required minlength="10"
                                                    class="w-full px-3 py-2 bg-[#293462] border border-[#D61C4E]/40 rounded-lg text-white placeholder-white/40 focus:outline-none focus:border-[#D61C4E]"></textarea>
                                            </div>
                                            <button type="submit" onclick="return confirm('¿Confirmás la baja definitiva de estas unidades?')"
                                                class="w-full py-2.5 rounded-lg bg-[#D61C4E] text-white font-bold hover:bg-[#b81742] transition-colors mt-2">
                                                Confirmar Baja
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal para anular -->
<dialog id="modal-anular" class="bg-transparent backdrop:bg-[#293462]/80 backdrop:backdrop-blur-sm p-0 m-auto w-full max-w-md rounded-2xl shadow-2xl border border-white/20">
    <div class="bg-[#1f2850] p-6 text-white w-full">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-[#ff5c86]">Anular Movimiento</h3>
            <button onclick="document.getElementById('modal-anular').close()" class="p-1 text-white/50 hover:text-white rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <p class="text-sm text-white/70 mb-5">
            Esto revertirá el impacto en el stock. El movimiento no se borrará, quedará marcado como anulado.
        </p>
        <form action="{{ route('movimientos.anular', $movimiento->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium">Motivo de anulación</label>
                <input type="text" name="motivo_anulacion" required placeholder="Ej. Error de tipeo, asignación incorrecta"
                    class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#ff5c86]">
            </div>
            <div class="flex gap-3 justify-end mt-6">
                <button type="button" onclick="document.getElementById('modal-anular').close()"
                    class="px-5 py-2.5 rounded-xl border border-white/20 hover:bg-white/5 transition-colors font-semibold">
                    Cancelar
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-[#D61C4E] hover:bg-[#b81742] text-white transition-colors font-bold shadow-[0_0_15px_rgba(214,28,78,0.3)]">
                    Confirmar Anulación
                </button>
            </div>
        </form>
    </div>
</dialog>
@endsection
