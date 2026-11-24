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
        Schema::create('informes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->unique()->constrained('tickets')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('titulo', 500);
            $table->foreignId('funcionario_id')->constrained('funcionarios')->restrictOnDelete();
            $table->string('tipo_problema', 60);
            $table->foreignId('detalle_id')->nullable()->constrained('detalles_problemas')->restrictOnDelete();
            $table->foreignId('solucion_id')->nullable()->constrained('detalles_soluciones')->restrictOnDelete();
            $table->longText('observacion')->nullable();
            $table->string('imagen_problema', 255)->nullable();
            $table->index('tipo_problema');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informes');
    }
};
