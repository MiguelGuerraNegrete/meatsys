<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** RF11: gestión de usuarios (solo administrador). */
class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = Usuario::with('rol')
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('nombres', 'like', "%$t%")
                ->orWhere('apellidos', 'like', "%$t%")->orWhere('usuario', 'like', "%$t%")->orWhere('documento', 'like', "%$t%")))
            ->orderByDesc('activo')->orderBy('nombres')->paginate(15)->withQueryString();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new Usuario(['activo' => true]), 'roles' => Rol::all()]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $datos['fechaCreacion'] = now()->toDateString();
        $datos['activo'] = true;
        Usuario::create($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function edit(Usuario $usuario)
    {
        return view('usuarios.form', ['usuario' => $usuario, 'roles' => Rol::all()]);
    }

    public function update(Request $request, Usuario $usuario)
    {
        $datos = $this->validar($request, $usuario);
        if (empty($datos['contrasena'])) {
            unset($datos['contrasena']);
        }
        if ($usuario->is($request->user()) && (int) $datos['Rol_idRol'] !== $usuario->Rol_idRol) {
            return back()->withErrors(['Rol_idRol' => 'No puede cambiar su propio rol.'])->withInput();
        }
        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function toggle(Request $request, Usuario $usuario)
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puede desactivar su propia cuenta.']);
        }
        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado: ya no podrá iniciar sesión.');
    }

    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $id = $usuario?->idUsuario;

        return $request->validate([
            'Rol_idRol' => ['required', 'exists:rol,idRol'],
            'documento' => ['required', 'string', 'max:15', Rule::unique('usuario', 'documento')->ignore($id, 'idUsuario')],
            'nombres' => ['required', 'string', 'max:60'],
            'apellidos' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:80', Rule::unique('usuario', 'email')->ignore($id, 'idUsuario')],
            'usuario' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('usuario', 'usuario')->ignore($id, 'idUsuario')],
            'telefono' => ['nullable', 'string', 'max:15'],
            'contrasena' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['Rol_idRol' => 'rol', 'contrasena' => 'contraseña']);
    }
}
