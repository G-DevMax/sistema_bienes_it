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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('solucion_id')
                ->nullable()
                ->after('detalle_id')
                ->constrained('detalles_soluciones')
                ->restrictOnDelete();
        });

        Schema::table('informes', function (Blueprint $table) {
            $table->foreignId('solucion_id')
                ->nullable()
                ->after('detalle_id')
                ->constrained('detalles_soluciones')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('informes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solucion_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solucion_id');
        });
    }
};
