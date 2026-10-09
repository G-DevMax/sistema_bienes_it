<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

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
}
