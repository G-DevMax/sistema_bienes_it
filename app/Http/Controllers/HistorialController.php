<?php

namespace App\Http\Controllers;

use App\Models\historial_cambios;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistorialController extends Controller
{
    private const MAX_MESES_RANGO = 3;

    /**
     * Descarga los registros de historial de cambios en formato JSON.
     */
    public function descargar(Request $request): StreamedResponse
    {
        $datos = $this->validateWithIndexRedirect($request, [
            'fecha_inicio' => ['required', 'date', 'after_or_equal:2026-01-01'],
            'fecha_fin' => [
                'required',
                'date',
                'after_or_equal:fecha_inicio',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $inicio = Carbon::make($request->input('fecha_inicio'));
                    $fin = Carbon::make($value);
                    if ($inicio && $fin && $fin->gt($inicio->copy()->addMonthsNoOverflow(self::MAX_MESES_RANGO))) {
                        $fail('El rango máximo permitido para descargar el historial es de '.self::MAX_MESES_RANGO.' meses.');
                    }
                },
            ],
        ], [
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_inicio.after_or_equal' => 'La fecha de inicio debe ser a partir del 01/01/2026.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ], 'usuarios.index');

        $fechaInicio = $datos['fecha_inicio'].' 00:00:00';
        $fechaFin = $datos['fecha_fin'].' 23:59:59';

        $consulta = historial_cambios::with('user:id,name')
            ->whereBetween('fecha_cambio', [$fechaInicio, $fechaFin])
            ->orderByDesc('fecha_cambio')
            ->orderByDesc('id');

        $fileName = 'historial_cambios_'.date('Ymd_His').'.json';

        return response()->streamDownload(function () use ($consulta): void {
            echo "[\n";
            $primero = true;

            foreach ($consulta->lazy(500) as $registro) {
                if (! $primero) {
                    echo ",\n";
                }
                $primero = false;

                echo json_encode([
                    'id' => $registro->id,
                    'usuario' => $registro->user?->name ?? 'Sistema/Desconocido',
                    'entidad' => class_basename($registro->entidad),
                    'entidad_id' => $registro->entidad_id,
                    'accion' => $registro->accion,
                    'detalle' => $registro->detalle !== null ? json_decode($registro->detalle) : null,
                    'fecha_cambio' => $registro->fecha_cambio->format('Y-m-d H:i:s'),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            echo "\n]";
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }
}
