<?php

namespace App\Http\Controllers;

use App\Mail\FacturaVenta;
use App\Models\Caja;
use App\Models\Pedido;
use App\Models\Venta;
use App\Services\FacturaService;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** RF2 / RF3: ventas al público y facturación. */
class VentaController extends Controller
{
    public function index(Request $request)
    {
        $ventas = Venta::with('cliente', 'usuario', 'factura')
            ->when($request->desde, fn ($q, $d) => $q->whereDate('fechaHora', '>=', $d))
            ->when($request->hasta, fn ($q, $h) => $q->whereDate('fechaHora', '<=', $h))
            ->when($request->metodo, fn ($q, $m) => $q->where('metodoPago', $m))
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->whereHas('factura', fn ($f) => $f->where('numeroFactura', 'like', "%$t%"))
                ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'like', "%$t%"))))
            ->latest('fechaHora')->paginate(20)->withQueryString();

        return view('ventas.index', compact('ventas'));
    }

    public function create(Request $request)
    {
        $pedido = $request->pedido ? Pedido::with('cliente', 'detalles.producto')->find($request->pedido) : null;
        if ($pedido && in_array($pedido->estado, ['entregado', 'cancelado'])) {
            return redirect()->route('pedidos.show', $pedido)->withErrors(['pedido' => 'Este pedido ya fue '.$pedido->estado.'.']);
        }

        return view('ventas.create', ['caja' => Caja::abierta(), 'pedido' => $pedido]);
    }

    public function store(Request $request, VentaService $servicio)
    {
        $datos = $request->validate([
            'cliente_id' => ['nullable', 'exists:cliente,idCliente'],
            'pedido_id' => ['nullable', 'exists:pedido,idPedido'],
            'metodo_pago' => ['required', Rule::in(['efectivo', 'tarjeta', 'transferencia'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:producto,idProducto'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ], ['items.required' => 'Agregue al menos un producto a la venta.'], ['metodo_pago' => 'método de pago']);

        $venta = $servicio->registrar($datos, $request->user());

        return redirect()->route('ventas.show', $venta)->with('ok', 'Venta registrada. Factura '.$venta->factura->numeroFactura.' generada.')->with('imprimir', true);
    }

    public function show(Venta $venta)
    {
        $venta->load('cliente', 'usuario', 'factura', 'detalles.producto', 'detalles.lote');

        return view('ventas.show', ['venta' => $venta, 'lineas' => $venta->lineasAgrupadas()]);
    }

    public function factura(Venta $venta, FacturaService $facturas)
    {
        $factura = $venta->factura ?: $facturas->generar($venta);

        return response()->file($facturas->rutaAbsoluta($factura), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$factura->numeroFactura.'.pdf"',
        ]);
    }

    public function enviar(Request $request, Venta $venta, FacturaService $facturas)
    {
        $request->validate(['email' => ['required', 'email']]);
        $factura = $venta->factura ?: $facturas->generar($venta);
        Mail::to($request->email)->send(new FacturaVenta($factura, $facturas->rutaAbsoluta($factura)));

        return back()->with('ok', 'Factura enviada a '.$request->email.'.');
    }
}
