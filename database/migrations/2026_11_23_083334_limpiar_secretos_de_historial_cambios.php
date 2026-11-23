<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('historial_cambios')
            ->where(function ($q): void {
                $q->where('detalle', 'like', '%password%')
                    ->orWhere('detalle', 'like', '%remember_token%');
            })
            ->orderBy('id')
            ->chunkById(200, function ($registros): void {
                foreach ($registros as $registro) {
                    $detalle = json_decode((string) $registro->detalle, true);
                    if (! is_array($detalle)) {
                        continue;
                    }

                    foreach (['original', 'cambiado'] as $clave) {
                        if (isset($detalle[$clave]) && is_array($detalle[$clave])) {
                            $detalle[$clave] = Arr::except($detalle[$clave], ['password', 'remember_token']);
                        }
                    }

                    DB::table('historial_cambios')
                        ->where('id', $registro->id)
                        ->update(['detalle' => json_encode($detalle)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Limpieza de datos sensibles irreversible.
    }
};
