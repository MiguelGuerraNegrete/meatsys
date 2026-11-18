<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Services\CajaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF9: caja diaria. */
class CajaController extends Controller
{
    public function index(CajaService $servicio)
    {
        $caja = Caja::abierta();

        return view('caja.index', [
            'caja' => $caja?->load('movimientos', 'usuario'),
            'resumen' => $caja ? $servicio->resumen($caja) : null,
            'historial' => Caja::with('usuario')->where('estado', 'cerrada')->latest('fechaCierre')->limit(15)->get(),
        ]);
    }

    public function abrir(Request $request, CajaService $servicio)
    {
        $request->validate(['montoInicial' => ['required', 'numeric', 'min:0']], [], ['montoInicial' => 'monto inicial']);
        $servicio->abrir($request->user(), (float) $request->montoInicial);

        return redirect()->route('caja.index')->with('ok', 'Caja abierta. Ya puede registrar ventas.');
    }

    public function movimiento(Request $request, CajaService $servicio)
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['ingreso', 'egreso'])],
            'concepto' => ['required', 'string', 'max:150'],
            'monto' => ['required', 'numeric', 'gt:0'],
        ]);
        $caja = Caja::abierta() ?? abort(422, 'No hay caja abierta.');
        $servicio->registrarMovimiento($caja, $datos['tipo'], $datos['concepto'], (float) $datos['monto']);

        return back()->with('ok', ucfirst($datos['tipo']).' registrado en caja.');
    }

    public function cierre(CajaService $servicio)
    {
        $caja = Caja::abierta();
        if (! $caja) {
            return redirect()->route('caja.index')->withErrors(['caja' => 'No hay una caja abierta para cerrar.']);
        }

        return view('caja.cierre', ['caja' => $caja->load('movimientos', 'usuario'), 'resumen' => $servicio->resumen($caja)]);
    }

    public function cerrar(Request $request, CajaService $servicio)
    {
        $request->validate([
            'montoContado' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:300'],
        ], [], ['montoContado' => 'efectivo contado']);
        $caja = Caja::abierta() ?? abort(422, 'No hay caja abierta.');
        $caja = $servicio->cerrar($caja, (float) $request->montoContado, $request->observaciones);

        return redirect()->route('caja.show', $caja)->with('ok', 'Cierre de caja registrado.');
    }

    public function show(Caja $caja, CajaService $servicio)
    {
        return view('caja.show', ['caja' => $caja->load('movimientos', 'usuario'), 'resumen' => $servicio->resumen($caja)]);
    }

    public function pdf(Caja $caja, CajaService $servicio)
    {
        $caja->load('movimientos', 'usuario');
        $resumen = $servicio->resumen($caja);

        return Pdf::loadView('pdf.cierre', compact('caja', 'resumen'))->stream('cierre-caja-'.$caja->idCaja.'.pdf');
    }
}
