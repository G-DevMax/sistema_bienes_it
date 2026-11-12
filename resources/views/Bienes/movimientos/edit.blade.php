@extends('layouts.app')

@section('title', 'Editar Movimiento')

@section('content')
    @php
        // Mock data para previsualización
        if (!isset($movimiento)) {
            $movimiento = (object) [
                'id' => 1,
                'item_id' => 1,
                'funcionario_id' => 1,
                'tipo_movimiento' => 'Asignación',
                'fecha_movimiento' => '2023-10-15T08:30',
                'fecha_devuelto' => null,
                'cantidad' => 1,
                'motivo' => '',
            ];
        }
    @endphp
    <div class="w-full max-w-4xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('movimientos.index') }}"
                class="p-2 text-white/50 hover:text-blue-400 hover:bg-blue-400/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-indigo-300 bg-clip-text text-transparent">
                    Editar Movimiento
                </h1>
                <p class="text-white/70 mt-1">Actualiza los datos de la asignación o devolución</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('movimientos.update', $movimiento->id ?? 1) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

                    <!-- Item -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="item_id" class="block mb-2 text-sm font-medium text-white/90">Bien / Item</label>
                        <select id="item_id" name="item_id"
                            class="searchable-select w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            @if (isset($items))
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}"
                                        {{ $item->id == $movimiento->item_id ? 'selected' : '' }}>
                                        {{ $item->descripcion }} (Código: {{ $item->codigo_patrimonial }})
                                    </option>
                                @endforeach
                            @else
                                <option value="1" selected>Notebook Dell Latitud 5480 (Código: 12001.23.04.2815)
                                </option>
                            @endif
                        </select>
                    </div>

                    <!-- Funcionario -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="funcionario_id" class="block mb-2 text-sm font-medium text-white/90">Funcionario
                            Receptor</label>
                        <select id="funcionario_id" name="funcionario_id"
                            class="searchable-select w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            @if (isset($funcionarios))
                                @foreach ($funcionarios as $func)
                                    <option value="{{ $func->id }}"
                                        {{ $func->id == $movimiento->funcionario_id ? 'selected' : '' }}>
                                        {{ $func->nombre }}
                                    </option>
                                @endforeach
                            @else
                                <option value="1" selected>Juan Perez</option>
                            @endif
                        </select>
                    </div>

                    <!-- Tipo de Movimiento -->
                    <div>
                        <label for="tipo_movimiento" class="block mb-2 text-sm font-medium text-white/90">Tipo de
                            Movimiento</label>
                        <select id="tipo_movimiento" name="tipo_movimiento"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all appearance-none"
                            required>
                            <option value="Asignación" {{ $movimiento->tipo_movimiento == 'Asignación' ? 'selected' : '' }}>
                                Asignación</option>
                            <option value="Préstamo" {{ $movimiento->tipo_movimiento == 'Préstamo' ? 'selected' : '' }}>
                                Préstamo</option>
                            <option value="Devolución"
                                {{ $movimiento->tipo_movimiento == 'Devolución' ? 'selected' : '' }}>Devolución</option>
                            <option value="Baja" {{ $movimiento->tipo_movimiento == 'Baja' ? 'selected' : '' }}>Baja
                            </option>
                        </select>
                    </div>

                    <!-- Cantidad -->
                    <div>
                        <label for="cantidad" class="block mb-2 text-sm font-medium text-white/90">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" value="{{ $movimiento->cantidad }}"
                            min="1"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            required>
                    </div>

                    <!-- Fecha de Movimiento -->
                    <div>
                        <label for="fecha_movimiento" class="block mb-2 text-sm font-medium text-white/90">Fecha de
                            Movimiento</label>
                        <input type="datetime-local" id="fecha_movimiento" name="fecha_movimiento"
                            value="{{ $movimiento->fecha_movimiento }}"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            required>
                    </div>

                    <!-- Fecha de Devolución -->
                    <div>
                        <label for="fecha_devuelto" class="block mb-2 text-sm font-medium text-white/90">Fecha de Devolución
                            <span class="text-white/50 text-xs font-normal">(Opcional)</span></label>
                        <input type="datetime-local" id="fecha_devuelto" name="fecha_devuelto"
                            value="{{ $movimiento->fecha_devuelto }}"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all">
                    </div>

                    <!-- Motivo -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="motivo" class="block mb-2 text-sm font-medium text-white/90">Motivo / Observación
                            <span class="text-white/50 text-xs font-normal">(Opcional)</span></label>
                        <textarea id="motivo" name="motivo" rows="2"
                            class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-400/30 transition-all"
                            placeholder="Especificar detalles adicionales, razón de baja, etc.">{{ $movimiento->motivo }}</textarea>
                    </div>
                </div> <!-- Fin del grid -->

                <!-- Botones -->
                <div class="w-full flex items-center justify-start gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('movimientos.index') }}">
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
                        Actualizar Movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
