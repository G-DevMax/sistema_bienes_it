<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\rol;

class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuarios = User::paginate(10);

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = rol::all();

        return view('usuarios.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $nuevo_usuario = new User();
        $nuevo_usuario->name = $request->name;
        $nuevo_usuario->email = $request->email;
        $nuevo_usuario->password = $request->password;
        $nuevo_usuario->rol_id = $request->rol_id;
        $nuevo_usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $usuario = User::find($id);
        $roles = rol::all();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $usuario_editado = User::find($id);
        $usuario_editado->name = $request->name;
        $usuario_editado->email = $request->email;

        // en caso de que se deje vacio la contraseña, no se trata de actualizar (es opcional)
        if ($request->password != "") {
            $usuario_editado->password = $request->password;
        }

        $usuario_editado->estado_user = $request->estado_user;
        $usuario_editado->rol_id = $request->rol_id;
        $usuario_editado->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario_eliminar = User::find($id);
        $usuario_eliminar->estado_user = 0;
        $usuario_eliminar->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario desactivado exitosamente');
    }
}
