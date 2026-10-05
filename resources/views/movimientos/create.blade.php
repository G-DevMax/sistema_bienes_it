@extends('layouts.app')

@section('title', 'Registrar Movimiento')

@section('content')
    <div class="w-full max-w-4xl animate-fade-in-up">
        <!-- Encabezado -->
        <div class="flex items-center gap-4 mb-8">
            <a href="{{ route('movimientos.index') }}"
                class="p-2 text-white/50 hover:text-[#1CD6CE] hover:bg-[#1CD6CE]/10 rounded-xl transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-[#1CD6CE] to-[#FEDB39] bg-clip-text text-transparent">
                    Registrar Movimiento
                </h1>
                <p class="text-white/70 mt-1">Completa los datos para asignar o transferir un bien</p>
            </div>
        </div>

        <!-- Formulario -->
        <div class="bg-[#293462]/60 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-[0_0_40px_rgba(0,0,0,0.3)]">
            <form action="{{ route('movimientos.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    
                    <!-- Item -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="item_id" class="block mb-2 text-sm font-medium text-white/90">Bien / Item</label>
                        <select id="item_id" name="item_id"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            required>
                            <option disabled selected>Selecciona un bien</option>
                            @if(isset($items))
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->descripcion }} (Código: {{ $item->codigo_patrimonial }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Funcionario -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="funcionario_id" class="block mb-2 text-sm font-medium text-white/90">Funcionario Receptor</label>
                        <select id="funcionario_id" name="funcionario_id"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            required>
                            <option disabled selected>Selecciona un funcionario</option>
                            @if(isset($funcionarios))
                                @foreach ($funcionarios as $func)
                                    <option value="{{ $func->id }}">{{ $func->nombre }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Tipo de Movimiento -->
                    <div>
                        <label for="tipo_movimiento" class="block mb-2 text-sm font-medium text-white/90">Tipo de Movimiento</label>
                        <select id="tipo_movimiento" name="tipo_movimiento"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all appearance-none"
                            required>
                            <option value="Asignación" selected>Asignación</option>
                            <option value="Préstamo">Préstamo</option>
                            <option value="Devolución">Devolución</option>
                            <option value="Baja">Baja</option>
                        </select>
                    </div>

                    <!-- Cantidad -->
                    <div>
                        <label for="cantidad" class="block mb-2 text-sm font-medium text-white/90">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad" value="1" min="1"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                            required>
                    </div>

                    <!-- Fecha de Movimiento -->
                    <div>
                        <label for="fecha_movimiento" class="block mb-2 text-sm font-medium text-white/90">Fecha de Movimiento</label>
                        <input type="datetime-local" id="fecha_movimiento" name="fecha_movimiento"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                            required>
                    </div>

                    <!-- Fecha de Devolución -->
                    <div>
                        <label for="fecha_devuelto" class="block mb-2 text-sm font-medium text-white/90">Fecha de Devolución <span class="text-white/50 text-xs font-normal">(Opcional)</span></label>
                        <input type="datetime-local" id="fecha_devuelto" name="fecha_devuelto"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all">
                    </div>

                    <!-- Motivo -->
                    <div class="col-span-1 md:col-span-2">
                        <label for="motivo" class="block mb-2 text-sm font-medium text-white/90">Motivo / Observación <span class="text-white/50 text-xs font-normal">(Opcional)</span></label>
                        <textarea id="motivo" name="motivo" rows="2"
                            class="w-full px-4 py-3 bg-[#293462] border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-[#1CD6CE] focus:ring-2 focus:ring-[#1CD6CE]/30 transition-all"
                            placeholder="Especificar detalles adicionales, razón de baja, etc."></textarea>
                    </div>
                </div> <!-- Fin del grid -->

                <!-- Botones -->
                <div class="w-full flex items-center justify-start gap-4 mt-8 pt-6 border-t border-white/10">
                    <a href="{{ route('movimientos.index') }}">
                        <button type="button"
                            class="px-8 py-3 bg-gradient-to-r from-[#D61C4E] to-[#b81742] hover:opacity-90 text-white font-bold rounded-xl shadow-[0_0_15px_rgba(214,28,78,0.3)] hover:shadow-[0_0_25px_rgba(214,28,78,0.5)] transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    </a>
                    <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-[#1CD6CE] to-[#15b0aa] hover:opacity-90 text-[#293462] font-bold rounded-xl shadow-[0_0_15px_rgba(28,214,206,0.3)] hover:shadow-[0_0_25px_rgba(28,214,206,0.5)] transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardar Movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
