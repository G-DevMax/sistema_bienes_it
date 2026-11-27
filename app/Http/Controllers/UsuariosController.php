<?php

namespace App\Http\Controllers;

use App\Models\rol;
use App\Models\Tickets\Ticket;
use App\Models\Tickets\TicketHistory;
use App\Models\User;
use App\Services\LoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UsuariosController extends Controller
{
    public function index()
    {
        $usuarios = User::with('rol')
            ->withCount('ticketsAsignados')
            ->orderBy('name', 'ASC')
            ->paginate(10);

        $tecnicosActivos = User::tecnicosActivos()
            ->orderBy('name', 'ASC')
            ->get(['id', 'name']);

        return view('usuarios.index', compact('usuarios', 'tecnicosActivos'));
    }

    public function create()
    {
        $roles = rol::orderBy('nombre', 'ASC')->get();

        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validarUsuario($request, true);

        return $this->ejecutarAccion(function () use ($datos): void {
            User::create([
                'name' => Str::title($datos['name']),
                'email' => strtolower($datos['email']),
                'password' => $datos['password'],
                'rol_id' => $datos['rol_id'],
                'estado_user' => 1,
            ]);

            LoggerService::info('Usuario creado', [
                'name' => $datos['name'],
                'email' => $datos['email'],
                'rol' => $datos['rol_id'],
            ]);
        }, 'usuarios.index', 'Usuario creado exitosamente.', 'Error al crear usuario.', 'Error al crear usuario', [
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol_id,
        ]);
    }

    public function edit(User $usuario)
    {
        $roles = rol::orderBy('nombre', 'ASC')->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $esPropioUsuario = (int) Auth::id() === (int) $usuario->id;

        if ($esPropioUsuario && (
            ($request->has('rol_id') && (int) $request->input('rol_id') !== (int) $usuario->rol_id)
            || ($request->has('estado_user') && (int) $request->input('estado_user') !== (int) $usuario->estado_user)
        )) {
            LoggerService::warning('Usuario intentó modificar su propio rol o estado', [
                'rol_actual' => $usuario->rol_id,
                'rol_solicitado' => $request->input('rol_id'),
                'estado_actual' => $usuario->estado_user,
                'estado_solicitado' => $request->input('estado_user'),
            ]);

            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes cambiar tu propio rol ni modificar el estado de tu cuenta en sesión.');
        }

        $datos = $this->validarUsuario($request, false, $usuario->id, ! $esPropioUsuario);

        return $this->ejecutarAccion(function () use ($request, $usuario, $datos, $esPropioUsuario): ?RedirectResponse {
            $cambioPassword = $request->filled('password');

            $redireccion = DB::transaction(function () use ($request, $usuario, $datos, $esPropioUsuario, $cambioPassword): ?RedirectResponse {
                if (! $esPropioUsuario && (int) $datos['estado_user'] === 0) {
                    $redir = $this->gestionarTicketsAlDesactivar($request, $usuario);
                    if ($redir !== null) {
                        return $redir;
                    }
                }

                $usuario->name = Str::title($datos['name']);
                $usuario->email = strtolower($datos['email']);

                if ($cambioPassword) {
                    $usuario->password = $datos['password'];
                }

                if (! $esPropioUsuario) {
                    $usuario->estado_user = $datos['estado_user'];
                    $usuario->rol_id = $datos['rol_id'];
                }

                // Rotar token al cambiar contraseña o desactivar para invalidar cookies "recordarme"
                if ($cambioPassword || (int) $usuario->estado_user !== 1) {
                    $usuario->setRememberToken(Str::random(60));
                }

                $usuario->save();

                return null;
            });

            if ($redireccion !== null) {
                return $redireccion;
            }

            // Mantener la sesión actual del propio usuario al cambiar su propia contraseña
            if ($esPropioUsuario && $cambioPassword) {
                Auth::setUser($usuario);
            }

            LoggerService::info('Usuario actualizado', [
                'name' => $datos['name'],
                'email' => $datos['email'],
                'rol' => $usuario->rol_id,
            ]);

            return null;
        }, 'usuarios.index', 'Usuario actualizado exitosamente.', 'Error al actualizar usuario.', 'Error al actualizar usuario', [
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $usuario->rol_id,
        ]);
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ((int) Auth::id() === (int) $usuario->id) {
            LoggerService::warning('Usuario intentó desactivar su propia cuenta', [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol_id,
            ]);

            return redirect()->route('usuarios.index')->with('error', 'No puedes desactivar tu propia cuenta de usuario en sesión.');
        }

        return $this->ejecutarAccion(function () use ($request, $usuario): ?RedirectResponse {
            $redireccion = DB::transaction(function () use ($request, $usuario): ?RedirectResponse {
                $redir = $this->gestionarTicketsAlDesactivar($request, $usuario);
                if ($redir !== null) {
                    return $redir;
                }

                $usuario->forceFill([
                    'estado_user' => 0,
                    'remember_token' => Str::random(60),
                ])->save();

                return null;
            });

            if ($redireccion !== null) {
                return $redireccion;
            }

            LoggerService::info('Usuario desactivado', [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol_id,
            ]);

            return null;
        }, 'usuarios.index', 'Usuario desactivado exitosamente.', 'Error al desactivar usuario.', 'Error al desactivar usuario', [
            'name' => $usuario->name,
            'email' => $usuario->email,
            'rol' => $usuario->rol_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validarUsuario(Request $request, bool $esCreacion, ?int $ignoreId = null, bool $validarRolYEstado = true): array
    {
        $reglas = [
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email'.($ignoreId ? ','.$ignoreId : ''),
            'password' => ($esCreacion ? 'required' : 'nullable').'|string|min:4',
        ];

        if ($validarRolYEstado) {
            $reglas['rol_id'] = 'required|exists:roles,id';
            if (! $esCreacion) {
                $reglas['estado_user'] = 'required|in:0,1';
            }
        }

        return $this->validateWithIndexRedirect($request, $reglas, [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un formato de correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'rol_id.required' => 'Debe seleccionar un rol para el usuario.',
            'rol_id.exists' => 'El rol seleccionado no es válido.',
            'estado_user.required' => 'Debe definir el estado del usuario.',
            'estado_user.in' => 'El estado seleccionado no es válido.',
        ], 'usuarios.index');
    }

    /**
     * Reasigna los tickets asignados al técnico antes de desactivarlo o redirige solicitando técnico receptor.
     */
    private function gestionarTicketsAlDesactivar(Request $request, User $usuario): ?RedirectResponse
    {
        $ticketsAsignados = Ticket::where('asignado_a', $usuario->id)
            ->where('estado', Ticket::ESTADO_ASIGNADO)
            ->lockForUpdate()
            ->get();

        if ($ticketsAsignados->isEmpty()) {
            return null;
        }

        $nuevoTecnicoId = $request->input('reasignar_a');
        $nuevoTecnico = filled($nuevoTecnicoId)
            ? User::tecnicosActivos()->where('id', '!=', $usuario->id)->find($nuevoTecnicoId)
            : null;

        if (! $nuevoTecnico) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Este técnico tiene tickets asignados. Debe reasignarlos a otro técnico antes de desactivarlo.')
                ->with('reasignar_usuario_id', $usuario->id)
                ->with('reasignar_usuario_nombre', $usuario->name);
        }

        $hoy = now()->toDateString();
        $responsableId = Auth::id();

        foreach ($ticketsAsignados as $ticket) {
            $ticket->update(['asignado_a' => $nuevoTecnico->id]);

            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'funcionario_id' => $ticket->funcionario_id,
                'accion' => 'Ticket reasignado a '.$nuevoTecnico->name.' por desactivación de técnico',
                'responsable_id' => $responsableId,
                'fecha' => $hoy,
            ]);
        }

        return null;
    }
}
