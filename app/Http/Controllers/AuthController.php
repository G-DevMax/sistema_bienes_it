<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // si las credenciales son incorrectas, redirige al usuario a la ruta /login con errores
        return back()->withErrors(
            [
                'email' => 'Las credenciales no coinciden',
                'password' => 'Las credenciales no coinciden'
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
