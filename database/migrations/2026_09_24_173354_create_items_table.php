<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            // columna para la clave foránea de la categoría (FK)
            $table->foreignId('categoria_id')->constrained('categorias')->onUpdate('cascade')->onDelete('restrict');
            $table->string('descripcion', 300);
            // NULL = "No aplica" (item generico, ej. cables). Con valor = item serializado/unico (stock siempre 1)
            $table->string('codigo_patrimonial', 60)->unique()->nullable();
            // cantidad total que posee Informatica (incluye lo prestado/transferido, excluye lo dado de baja)
            $table->unsignedInteger('stock_total')->default(1);
            // cantidad fisicamente disponible en Informatica en este momento
            $table->unsignedInteger('stock_disponible')->default(1);
            // columna para la clave foránea del estado (FK)
            // serializados: Disponible/Transferido/Prestado/Inservible. Genericos: No Aplica (se muestra desglose)
            $table->foreignId('estado_id')->default(1)->constrained('estado')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
