<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\rol;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
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
        ]);

        $nuevo_usuario = new User();
        $nuevo_usuario->name = $request->name;
        $nuevo_usuario->email = $request->email;
        $nuevo_usuario->password = $request->password;
        $nuevo_usuario->rol_id = $request->rol_id;
        $nuevo_usuario->estado_user = 1;
        $nuevo_usuario->save();

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

        $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email,' . $usuario_editado->id,
            'password' => 'nullable|string|min:4',
            'rol_id' => 'required|exists:roles,id',
            'estado_user' => 'required|in:0,1',
        ], [
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
        ]);

        $usuario_editado->name = $request->name;
        $usuario_editado->email = $request->email;

        // Si se envió una nueva contraseña, se actualiza
        if ($request->filled('password')) {
            $usuario_editado->password = $request->password;
        }

        $usuario_editado->estado_user = $request->estado_user;
        $usuario_editado->rol_id = $request->rol_id;
        $usuario_editado->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario_eliminar = User::findOrFail($id);

        if (Auth::id() == $usuario_eliminar->id) {
            return redirect()->route('usuarios.index')->with('error', 'No puedes desactivar tu propia cuenta de usuario en sesión.');
        }

        $usuario_eliminar->estado_user = 0;
        $usuario_eliminar->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado exitosamente.');
    }
}
