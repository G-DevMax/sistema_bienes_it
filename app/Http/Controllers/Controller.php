<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
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
