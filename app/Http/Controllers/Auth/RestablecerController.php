<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\CodigoRestablecimiento;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/** Caso de uso extendido "Restablecer contraseña" con código de 6 dígitos. */
class RestablecerController extends Controller
{
    public function solicitar()
    {
        return view('auth.olvido');
    }

    public function enviar(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);
        $usuario = Usuario::where('email', $request->email)->where('activo', true)->first();

        if ($usuario) {
            $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $usuario->update([
                'resetCode' => $codigo,
                'resetCodeExpiration' => now()->addMinutes(config('meatsys.reset_code_minutos')),
            ]);
            Mail::to($usuario->email)->send(new CodigoRestablecimiento($usuario, $codigo));
        }

        // Mismo mensaje exista o no el correo, para no revelar cuentas
        return redirect()->route('password.reset', ['email' => $request->email])
            ->with('ok', 'Si el correo está registrado, recibirá un código de 6 dígitos válido por '.config('meatsys.reset_code_minutos').' minutos.');
    }

    public function formulario(Request $request)
    {
        return view('auth.restablecer', ['email' => $request->query('email')]);
    }

    public function restablecer(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'codigo' => ['required', 'digits:6'],
            'contrasena' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (! $usuario || ! $usuario->resetCode || ! hash_equals($usuario->resetCode, $request->codigo)
            || ! $usuario->resetCodeExpiration || $usuario->resetCodeExpiration->isPast()) {
            return back()->withInput($request->only('email'))->withErrors(['codigo' => 'El código no es válido o ya expiró.']);
        }

        $usuario->update(['contrasena' => $request->contrasena, 'resetCode' => null, 'resetCodeExpiration' => null]);
        RateLimiter::clear('login:'.Str::lower($usuario->usuario));

        return redirect()->route('login')->with('ok', 'Contraseña actualizada. Ya puede iniciar sesión.');
    }
}
