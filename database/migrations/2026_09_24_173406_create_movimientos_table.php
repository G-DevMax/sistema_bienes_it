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
            // id de lote cuando se registran varios items en un solo movimiento
            $table->uuid('lote_id')->nullable()->index();
            $table->foreignId('item_id')->constrained('items')->onUpdate('cascade')->onDelete('restrict');
            // puede ser null en ingresos y bajas realizadas directamente desde Informatica
            $table->foreignId('funcionario_id')->nullable()->constrained('funcionarios')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('restrict'); // se registra siempre la persona que autorizo/creo el movimiento
            // ingreso | prestamo | transferencia | devolucion | baja
            $table->string('tipo_movimiento', 20);
            // devoluciones y bajas de un item prestado/transferido apuntan al movimiento original
            $table->foreignId('movimiento_origen_id')->nullable()->constrained('movimientos')->onUpdate('cascade')->onDelete('restrict');
            $table->dateTime('fecha_movimiento');
            $table->date('fecha_devolucion_prevista')->nullable(); // obligatoria solo en prestamos
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('motivo', 255)->nullable(); // obligatorio en bajas
            $table->text('observaciones')->nullable(); // obligatoria en bajas de items prestados/transferidos (responsabilidad)
            // anulacion (los movimientos nunca se eliminan)
            $table->dateTime('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('restrict');
            $table->string('motivo_anulacion', 255)->nullable();
            $table->timestamps();

            $table->index(['item_id', 'tipo_movimiento']);
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
