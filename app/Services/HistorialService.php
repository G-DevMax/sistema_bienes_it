<?php

namespace App\Services;

use App\Models\historial_cambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class HistorialService
{
    /**
     * Registra un cambio en el historial.
     *
     * @param  Model  $modelo  El modelo que ha sido modificado, creado o eliminado.
     * @param  string  $accion  Tipo de acción: 'Create', 'Update', 'Delete'
     * @param  array<string, mixed>|null  $original  Los datos originales (antes del cambio).
     * @param  array<string, mixed>|null  $cambiado  Los datos nuevos (después del cambio).
     */
    public function registrarCambio(Model $modelo, string $accion, ?array $original = null, ?array $cambiado = null): void
    {
        $detalle = [
            'original' => $this->limpiarOcultos($modelo, $original),
            'cambiado' => $this->limpiarOcultos($modelo, $cambiado),
        ];

        historial_cambios::create([
            'user_id' => Auth::id(),
            'entidad' => get_class($modelo),
            'entidad_id' => $modelo->getKey(),
            'accion' => $accion,
            'detalle' => json_encode($detalle),
            'fecha_cambio' => now(),
        ]);
    }

    /**
     * Helper para registrar la creación de un modelo.
     */
    public function registrarCreacion(Model $modelo): void
    {
        $this->registrarCambio($modelo, 'Create', null, $modelo->toArray());
    }

    /**
     * Helper para registrar la actualización solo con los campos modificados.
     *
     * @param  array<string, mixed>  $original
     */
    public function registrarActualizacion(Model $modelo, array $original): void
    {
        $cambios = $modelo->getChanges();
        $columnasIgnoradas = array_filter([
            $modelo->usesTimestamps() ? $modelo->getUpdatedAtColumn() : null,
        ]);
        $clavesModificadas = array_values(array_diff(array_keys($cambios), $columnasIgnoradas));

        if ($clavesModificadas === [] || $clavesModificadas === ['remember_token']) {
            return;
        }

        $this->registrarCambio(
            $modelo,
            'Update',
            Arr::only($original, array_keys($cambios)),
            $cambios
        );
    }

    /**
     * Helper para registrar la eliminación de un modelo.
     */
    public function registrarEliminacion(Model $modelo): void
    {
        $this->registrarCambio($modelo, 'Delete', $modelo->toArray(), null);
    }

    /**
     * @param  array<string, mixed>|null  $datos
     * @return array<string, mixed>|null
     */
    private function limpiarOcultos(Model $modelo, ?array $datos): ?array
    {
        if ($datos === null) {
            return null;
        }

        return Arr::except($datos, $modelo->getHidden());
    }
}
