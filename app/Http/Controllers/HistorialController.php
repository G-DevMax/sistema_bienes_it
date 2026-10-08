<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\historial_cambios;

class HistorialController extends Controller
{
    /**
     * Descarga los registros de historial de cambios en formato JSON.
     */
    public function descargar(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date|after_or_equal:2026-01-01',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $fechaInicio = $request->input('fecha_inicio') . ' 00:00:00';
        $fechaFin = $request->input('fecha_fin') . ' 23:59:59';

        $historial = historial_cambios::with('user:id,name') // Asumiendo que users tiene campo name
            ->whereBetween('fecha_cambio', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_cambio', 'desc')
            ->get()
            ->map(function ($registro) {
                return [
                    'id' => $registro->id,
                    'usuario' => $registro->user ? $registro->user->name : 'Sistema/Desconocido',
                    'entidad' => class_basename($registro->entidad), // Solo el nombre corto del modelo
                    'entidad_id' => $registro->entidad_id,
                    'accion' => $registro->accion,
                    'detalle' => json_decode($registro->detalle),
                    'fecha_cambio' => $registro->fecha_cambio->format('Y-m-d H:i:s'),
                ];
            });

        $fileName = 'historial_cambios_' . date('Ymd_His') . '.json';

        return response()->streamDownload(function () use ($historial) {
            echo json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }
}
