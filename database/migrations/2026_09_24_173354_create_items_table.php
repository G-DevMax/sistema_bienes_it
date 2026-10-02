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
            $table->string('codigo_patrimonial', 60)->unique()->nullable();
            $table->integer('stock')->default(1);
            // columna para la clave foránea del estado (FK)
            $table->foreignId('estado_id')->constrained('estado')->onUpdate('cascade')->onDelete('restrict')->default(4);
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
