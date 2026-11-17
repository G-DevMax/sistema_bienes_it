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
            $table->foreignId('funcionario_id')->constrained('funcionarios')->cascadeOnDelete();
            $table->enum('tipo_problema', ['Equipo/Dispositivo', 'Programa/Sistema', 'Internet', 'Impresora/Escáner', 'Cuenta y Acceso', 'Consulta', 'Insumos']);
            $table->foreignId('detalle_id')->nullable()->constrained('detalles_problemas')->nullOnDelete();
            $table->string('descripcion_problema');
            $table->enum('estado', ['Pendiente', 'Asignado', 'Resuelto', 'Cerrado'])->default('Pendiente');
            $table->foreignId('asignado_a')->nullable()->constrained('users')->cascadeOnDelete();
            $table->date('fecha_creacion')->default(now());
            $table->date('fecha_cierre')->nullable();
            $table->string('observacion')->nullable();
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
