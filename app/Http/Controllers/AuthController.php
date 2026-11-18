<?php

namespace App\Http\Controllers;

use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        try {
            $remember = $request->boolean('remember', true);

            if (Auth::attempt(array_merge($credenciales, ['estado_user' => 1]), $remember)) {
                $request->session()->regenerate();

                LoggerService::info('Inicio de Sesión', [
                    'email' => $credenciales['email'],
                ]);

                return redirect()->intended('/items');
            }

            LoggerService::warning('Intento fallido de inicio de sesión', [
                'email' => $credenciales['email'],
            ]);
        } catch (\Exception $e) {
            LoggerService::error('Error al iniciar sesión', [
                'email' => $credenciales['email'],
            ], $e);

            return redirect()->route('login')->with('error', 'Error al iniciar sesión: '.$e->getMessage());
        }

        return back()->withInput($request->only('email'))->withErrors([
            'error' => 'Las credenciales no coinciden o su cuenta está desactivada.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
