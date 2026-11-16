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
            $table->foreignId("funcionario_id")->constrained("funcionarios")->cascadeOnDelete();
            $table->enum("tipo_problema", ["Hardware", "Software", "Red", "Cuenta y Acceso", "Consulta", "Insumo"]);
            $table->string("descripcion_problea");
            $table->enum("estado", ["Pendiente", "Asignado", "Resuelto", "Cerrado"])->default("Pendiente");
            $table->foreignId("asignado_a")->nullable()->constrained("users")->cascadeOnDelete();
            $table->date('fecha_creacion')->default(now());
            $table->date('fecha_cierre')->nullable();
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
