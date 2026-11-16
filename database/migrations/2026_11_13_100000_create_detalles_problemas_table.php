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
        Schema::create('detalles_problemas', function (Blueprint $table) {
            $table->id();
            $table->enum("tipo_problema", ["Hardware", "Software", "Red", "Impresora", "Cuentas y Accesos", "Consulta", "Insumo"]);
            $table->string("detalle");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_problemas');
    }
};
