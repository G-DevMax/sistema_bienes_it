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
        Schema::create('historial_cambios', function (Blueprint $table) {
            $table->id();
            // columna fk del usuario que ha realizado los cambios
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->string('entidad', 300)->nullable(); // tabla de donde proviene el cambio
            $table->integer('entidad_id')->nullable(); // id de la fila modificada de la tabla
            $table->string('accion', 20)->nullable(); // accion realizada (Create, Update, Delete)
            $table->json('detalle')->nullable(); // detalles de los cambios (clave:valor)
            $table->dateTime('fecha_cambio')->useCurrent(); // fecha y hora en la que se realizo el cambio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_cambios');
    }
};
