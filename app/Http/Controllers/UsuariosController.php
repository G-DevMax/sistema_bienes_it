<?php

namespace App\Http\Controllers;

use App\Models\rol;
use App\Models\User;
use App\Services\LoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuarios = User::with('rol')->orderBy('name', 'ASC')->paginate(10);

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = rol::orderBy('nombre', 'ASC')->get();

        return view('usuarios.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateWithIndexRedirect($request, [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:4',
            'rol_id' => 'required|exists:roles,id',
        ], [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un formato de correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'rol_id.required' => 'Debe seleccionar un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
        ], 'usuarios.index');

        try {
            User::create([
                'name' => Str::title($request->name),
                'email' => strtolower($request->email),
                'password' => $request->password,
                'rol_id' => $request->rol_id,
                'estado_user' => 1,
            ]);

            LoggerService::info('Usuario creado', [
                'name' => $request->name,
                'email' => $request->email,
                'rol' => $request->rol_id,
                'user_id' => Auth::id(), // id del administrador que creó el usuario
            ]);
        } catch (\Exception $exception) {
            LoggerService::error('Error al crear usuario', [
                'name' => $request->name,
                'email' => $request->email,
                'rol' => $request->rol_id,
                'user_id' => Auth::id(),
            ], $exception);

            return redirect()->route('usuarios.index')->with('error', 'Error al crear usuario.');
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('usuarios.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $usuario = User::findOrFail($id);
        $roles = rol::orderBy('nombre', 'ASC')->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $usuario_editado = User::findOrFail($id);
        $esPropioUsuario = (int) Auth::id() === (int) $usuario_editado->id;

        if ($esPropioUsuario && (
            ($request->has('rol_id') && (int) $request->input('rol_id') !== (int) $usuario_editado->rol_id)
            || ($request->has('estado_user') && (int) $request->input('estado_user') !== (int) $usuario_editado->estado_user)
        )) {
            LoggerService::warning('Usuario intentó modificar su propio rol o estado', [
                'user_id' => Auth::id(),
                'rol_actual' => $usuario_editado->rol_id,
                'rol_solicitado' => $request->input('rol_id'),
                'estado_actual' => $usuario_editado->estado_user,
                'estado_solicitado' => $request->input('estado_user'),
            ]);

            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes cambiar tu propio rol ni modificar el estado de tu cuenta en sesión.');
        }

        $reglas = [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email,'.$usuario_editado->id,
            'password' => 'nullable|string|min:4',
        ];

        if (! $esPropioUsuario) {
            $reglas['rol_id'] = 'required|exists:roles,id';
            $reglas['estado_user'] = 'required|in:0,1';
        }

        $this->validateWithIndexRedirect($request, $reglas, [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un formato de correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'password.min' => 'La nueva contraseña debe tener al menos :min caracteres.',
            'rol_id.required' => 'Debe seleccionar un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'estado_user.required' => 'Debe definir el estado del usuario.',
            'estado_user.in' => 'El estado seleccionado no es válido.',
        ], 'usuarios.index');

        try {
            $usuario_editado->name = Str::title($request->name);
            $usuario_editado->email = strtolower($request->email);
            $cambioPassword = $request->filled('password');

            // Si se envió una nueva contraseña, se actualiza
            if ($cambioPassword) {
                $usuario_editado->password = $request->password;
            }

            if (! $esPropioUsuario) {
                $usuario_editado->estado_user = $request->estado_user;
                $usuario_editado->rol_id = $request->rol_id;
            }

            // Rotar token al cambiar contraseña o desactivar para invalidar cookies "recordarme"
            if ($cambioPassword || (int) $usuario_editado->estado_user !== 1) {
                $usuario_editado->setRememberToken(Str::random(60));
            }

            $usuario_editado->save();

            // Mantener la sesión actual del propio usuario al cambiar su propia contraseña
            if ($esPropioUsuario && $cambioPassword) {
                Auth::setUser($usuario_editado);
            }

            LoggerService::info('Usuario actualizado', [
                'name' => $request->name,
                'email' => $request->email,
                'rol' => $usuario_editado->rol_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $exception) {
            LoggerService::error('Error al actualizar usuario', [
                'name' => $request->name,
                'email' => $request->email,
                'rol' => $usuario_editado->rol_id,
                'user_id' => Auth::id(),
            ], $exception);

            return redirect()->route('usuarios.index')->with('error', 'Error al actualizar usuario.');
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario_eliminar = User::findOrFail($id);

        if (Auth::id() == $usuario_eliminar->id) {
            LoggerService::warning('Usuario intentó desactivar su propia cuenta', [
                'name' => $usuario_eliminar->name,
                'email' => $usuario_eliminar->email,
                'rol' => $usuario_eliminar->rol_id,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('usuarios.index')->with('error', 'No puedes desactivar tu propia cuenta de usuario en sesión.');
        }

        try {
            $usuario_eliminar->estado_user = 0;
            $usuario_eliminar->setRememberToken(Str::random(60));
            $usuario_eliminar->save();

            LoggerService::info('Usuario desactivado', [
                'name' => $usuario_eliminar->name,
                'email' => $usuario_eliminar->email,
                'rol' => $usuario_eliminar->rol_id,
                'user_id' => Auth::id(),
            ]);
        } catch (\Exception $exception) {
            LoggerService::error('Error al desactivar usuario', [
                'name' => $usuario_eliminar->name,
                'email' => $usuario_eliminar->email,
                'rol' => $usuario_eliminar->rol_id,
                'user_id' => Auth::id(),
            ], $exception);

            return redirect()->route('usuarios.index')->with('error', 'Error al desactivar usuario.');
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado exitosamente.');
    }
}
