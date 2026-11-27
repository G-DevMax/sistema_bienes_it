<?php

namespace App\Http\Controllers;

use App\Models\Tickets\Ticket;
use App\Services\LoggerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    /**
     * Valida la petición y si falla, redirige al index con un mensaje de error o advertencia.
     */
    protected function validateWithIndexRedirect(Request $request, array $rules, array $messages = [], string $redirectRoute = 'index')
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new HttpResponseException(
                redirect()->route($redirectRoute)
                    ->with('error', $validator->errors()->first())
                    ->withInput()
            );
        }

        return $validator->validated();
    }

    /**
     * Ejecuta una acción con manejo uniforme de errores, log y redirección.
     *
     * @param  string|array{0: string, 1?: mixed}  $rutaRedireccion
     * @param  array<string, mixed>  $contextoError
     */
    protected function ejecutarAccion(
        callable $accion,
        string|array $rutaRedireccion,
        string $mensajeExito,
        string $mensajeError,
        string $logError,
        array $contextoError = []
    ): RedirectResponse {
        try {
            $resultado = $accion();

            if ($resultado instanceof RedirectResponse) {
                return $resultado;
            }

            return $this->redirigirA($rutaRedireccion)
                ->with('success', is_string($resultado) ? $resultado : $mensajeExito);
        } catch (\Exception $e) {
            LoggerService::error($logError, $contextoError, $e);

            return $this->redirigirA($rutaRedireccion)
                ->with('error', $this->mensajeError($e, $mensajeError));
        }
    }

    /**
     * Valida un límite máximo de palabras preservando UTF-8.
     */
    protected function reglaMaxPalabras(int $max, string $mensaje): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail) use ($max, $mensaje): void {
            $palabras = preg_split('/\s+/u', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);
            if (count($palabras) > $max) {
                $fail($mensaje);
            }
        };
    }

    /**
     * Resuelve el mes seleccionado y sus límites en formato Y-m-d junto a etiquetas para la vista.
     *
     * @return array{mes: string, inicioMes: string, finMes: string, nombreMes: string, nombreMesSimple: string, anio: string, anioMinimo: int, anioMaximo: int}
     */
    protected function resolverMes(Request $request, int $anioMinimo, int $anioMaximo): array
    {
        $mes = Ticket::mesValidoEnRango($request->query('mes'), $anioMinimo, max($anioMaximo, now()->addDay()->year))
            ?? sprintf('%04d-%02d', max(now()->year, $anioMinimo), now()->month);

        [$year, $month] = explode('-', $mes);
        $anioMaximo = max($anioMaximo, (int) $year);

        $inicio = Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();
        $nombreMesSimple = ucfirst($inicio->locale('es')->monthName);

        return [
            'mes' => $mes,
            'inicioMes' => $inicio->toDateString(),
            'finMes' => $inicio->copy()->endOfMonth()->toDateString(),
            'nombreMes' => $nombreMesSimple.' '.$year,
            'nombreMesSimple' => $nombreMesSimple,
            'anio' => $year,
            'anioMinimo' => $anioMinimo,
            'anioMaximo' => $anioMaximo,
        ];
    }

    /**
     * @param  string|array{0: string, 1?: mixed}  $ruta
     */
    private function redirigirA(string|array $ruta): RedirectResponse
    {
        return is_array($ruta) ? redirect()->route(...$ruta) : redirect()->route($ruta);
    }

    /**
     * Devuelve un mensaje seguro para el usuario, conservando validaciones y traduciendo errores SQL comunes.
     */
    protected function mensajeError(\Throwable $e, string $mensajePorDefecto): string
    {
        if ($e instanceof ValidationException) {
            $prefijo = rtrim($mensajePorDefecto, '. ');

            return $prefijo.': '.$e->getMessage();
        }

        if ($e instanceof QueryException) {
            $codigoMysql = (int) ($e->errorInfo[1] ?? 0);

            return match ($codigoMysql) {
                1062 => 'Ya existe un registro con esos datos en el sistema.',
                1451 => 'No se puede eliminar o modificar el registro porque está vinculado a otros datos.',
                1452 => 'Uno de los registros relacionados que seleccionó no existe o ya no está disponible.',
                1265, 1406 => 'Uno de los valores ingresados no tiene un formato válido o supera la longitud permitida.',
                1205, 1213 => 'El sistema está procesando otra operación simultánea. Intente nuevamente.',
                default => $mensajePorDefecto,
            };
        }

        return $mensajePorDefecto;
    }
}
