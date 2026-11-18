<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Restringe el acceso por rol: ->middleware('rol:administrador,cajero') */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->activo) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['usuario' => 'Su cuenta está desactivada.']);
        }

        if ($roles && ! $usuario->tieneRol(...$roles)) {
            abort(403, 'No tiene permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
