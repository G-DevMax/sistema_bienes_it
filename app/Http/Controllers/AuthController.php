<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\LoggerService;

class AuthController extends Controller
{
    // formulario de login
    public function showLoginForm()
    {
        return view('login');
    }

    // metodo para procesar el login
    public function login(Request $request)
    {
        try {
            // validacion de los datos enviados por el formulario
            $credenciales = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ], [
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Debe ingresar un correo electrónico válido.',
                'password.required' => 'La contraseña es obligatoria.',
            ]);

            $remember = $request->boolean('remember');

            // si las credenciales son correctas, redirige al usuario a la ruta /items
            if (Auth::attempt($credenciales, $remember)) {
                $request->session()->regenerate();
                return redirect()->intended('/items');
            }

            LoggerService::info('Inicio de Sesión', [
                'email' => $credenciales['email'],
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al iniciar sesión', [
                'email' => $credenciales['email'],
            ], $e);

            return redirect()->route('login')->with('error', 'Error al iniciar sesión: ' . $e->getMessage());
        }
        // si las credenciales son incorrectas, redirige al usuario a la ruta /login con errores
        return back()->withErrors(
            [
                'error' => 'Las credenciales no coinciden',
                'warning' => 'Su cuenta esta desactivada'
            ]
        );
    }

    // metodo para cerrar sesion
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
