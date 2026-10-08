<?php

namespace App\Services;

use App\Models\historial_cambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class HistorialService
{
    /**
     * Registra un cambio en el historial.
     *
     * @param Model $modelo El modelo que ha sido modificado, creado o eliminado.
     * @param string $accion Tipo de acción: 'Create', 'Update', 'Delete'
     * @param array|null $original Los datos originales (antes del cambio). Si es null, se intenta obtener del modelo.
     * @param array|null $cambiado Los datos nuevos (después del cambio). Si es null, se intenta obtener del modelo.
     * @return void
     */
    public function registrarCambio(Model $modelo, string $accion, ?array $original = null, ?array $cambiado = null)
    {
        // Si no hay usuario logueado, podríamos registrar null o un usuario de sistema, aquí asumimos autenticado.
        $userId = Auth::id() ?? 1; // Asumimos un fallback si no hay sesión para evitar fallos.

        $detalle = [
            'original' => $original,
            'cambiado' => $cambiado,
        ];

        historial_cambios::create([
            'user_id' => $userId,
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
    public function registrarCreacion(Model $modelo)
    {
        $this->registrarCambio($modelo, 'Create', null, $modelo->toArray());
    }

    /**
     * Helper para registrar la actualización de un modelo.
     */
    public function registrarActualizacion(Model $modelo, array $original)
    {
        // En Laravel, los cambios se pueden obtener más precisamente pasando los originales que obtuvimos ANTES del save.
        $this->registrarCambio(
            $modelo, 
            'Update', 
            $original, 
            $modelo->toArray() // el estado actual
        );
    }

    /**
     * Helper para registrar la eliminación de un modelo.
     */
    public function registrarEliminacion(Model $modelo)
    {
        $this->registrarCambio($modelo, 'Delete', $modelo->toArray(), null);
    }
}
