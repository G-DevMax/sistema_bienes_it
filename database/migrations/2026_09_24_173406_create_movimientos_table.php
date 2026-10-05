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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            // columnas fk
            $table->foreignId('item_id')->constrained('items')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('funcionario_id')->constrained('funcionarios')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict'); // se registra siempre la persona que autorizo/creo el movimiento
            $table->string('tipo_movimiento', 60);
            $table->dateTime('fecha_movimiento');
            $table->dateTime('fecha_devuelto')->nullable(); // puede ser null porque solo se utiliza cuando el movimiento es un prestamo
            $table->integer('cantidad')->default(1);
            $table->string('motivo', 60)->nullable(); // puede ser null porque solo se utiliza cuando el movimiento es una baja
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
