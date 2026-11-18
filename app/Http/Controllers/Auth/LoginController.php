<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:30'],
            'contrasena' => ['required', 'string'],
        ]);

        $clave = 'login:'.Str::lower($datos['usuario']);
        $max = config('meatsys.login_max_intentos');

        if (RateLimiter::tooManyAttempts($clave, $max)) {
            $min = (int) ceil(RateLimiter::availableIn($clave) / 60);

            return back()->withInput($request->only('usuario'))->withErrors([
                'usuario' => "Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intente de nuevo en {$min} minuto(s) o restablezca su contraseña.",
            ]);
        }

        $usuario = Usuario::where('usuario', $datos['usuario'])->first();

        if (! $usuario || ! Auth::attempt(['usuario' => $datos['usuario'], 'password' => $datos['contrasena']])) {
            RateLimiter::hit($clave, config('meatsys.login_bloqueo_minutos') * 60);
            $restantes = RateLimiter::remaining($clave, $max);

            return back()->withInput($request->only('usuario'))->withErrors([
                'usuario' => 'Usuario o contraseña incorrectos.'.($restantes > 0 && $restantes < $max ? " Le quedan {$restantes} intento(s)." : ''),
            ]);
        }

        if (! $usuario->activo) {
            Auth::logout();

            return back()->withInput($request->only('usuario'))->withErrors(['usuario' => 'Su cuenta está desactivada. Contacte al administrador.']);
        }

        RateLimiter::clear($clave);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('ok', 'Sesión cerrada correctamente.');
    }
}
