<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Services\PagoProveedorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PagoProveedorController extends Controller
{
    /** Compras con saldo pendiente. */
    public function index()
    {
        $compras = Compra::with('proveedor')->where('saldoPendiente', '>', 0)->orderBy('fechaCompra')->get();

        return view('pagos.index', compact('compras'));
    }

    public function create(Compra $compra)
    {
        $compra->load('proveedor', 'detalles.producto', 'pagos.usuario');

        return view('pagos.create', compact('compra'));
    }

    public function store(Request $request, Compra $compra, PagoProveedorService $servicio)
    {
        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'metodoPago' => ['required', Rule::in(['efectivo', 'transferencia', 'otro'])],
            'fechaPago' => ['required', 'date', 'before_or_equal:now'],
        ], [], ['metodoPago' => 'método de pago', 'fechaPago' => 'fecha de pago']);

        $servicio->registrar($compra, (float) $datos['monto'], $datos['metodoPago'], $request->user(), $datos['fechaPago']);

        return redirect()->route('compras.show', $compra)->with('ok', 'Pago registrado y saldo actualizado.');
    }
}
