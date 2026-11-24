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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->string('tipo_problema', 60);
            $table->foreignId('detalles_problema_id')->nullable()->constrained('detalles_problemas')->restrictOnDelete();
            $table->foreignId('solucion_id')->nullable()->constrained('detalles_soluciones')->restrictOnDelete();
            $table->text('descripcion_problema');
            $table->string('imagen_problema', 255)->nullable();
            $table->enum('estado', ['Pendiente', 'Asignado', 'Cerrado'])->default('Pendiente');
            $table->foreignId('asignado_a')->nullable()->constrained('users')->restrictOnDelete();
            $table->date('fecha_creacion')->useCurrent();
            $table->date('fecha_cierre')->nullable();
            $table->text('observacion')->nullable();
            $table->index(['fecha_creacion', 'estado', 'fecha_cierre']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
