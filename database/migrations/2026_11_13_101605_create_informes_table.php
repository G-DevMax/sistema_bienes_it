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
            $table->string('titulo', 500);
            $table->foreignId('funcionario_id')->constrained('funcionarios')->cascadeOnDelete();
            $table->enum('tipo_problema', ['Hardware', 'Software', 'Red', 'Impresora', 'Cuentas y Accesos', 'Consulta']);
            $table->foreignId('detalle_id')->nullable()->constrained('detalles_problemas')->nullOnDelete();
            $table->longText('observacion')->nullable();
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
