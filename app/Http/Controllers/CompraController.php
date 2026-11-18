<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use Illuminate\Http\Request;

/** RF4: registro de ingreso de mercancía. */
class CompraController extends Controller
{
    public function index(Request $request)
    {
        $compras = Compra::with('proveedor', 'usuario')
            ->when($request->proveedor, fn ($q, $p) => $q->where('Proveedor_idProveedor', $p))
            ->when($request->estado, fn ($q, $e) => $q->where('estadoPago', $e))
            ->latest('fechaCompra')->paginate(15)->withQueryString();

        return view('compras.index', ['compras' => $compras, 'proveedores' => Proveedor::orderBy('razonSocial')->get()]);
    }

    public function create()
    {
        return view('compras.create', [
            'proveedores' => Proveedor::where('activo', true)->orderBy('razonSocial')->get(),
            'productos' => Producto::activos()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, CompraService $servicio)
    {
        $datos = $request->validate([
            'proveedor_id' => ['required', 'exists:proveedor,idProveedor'],
            'numero_factura' => ['nullable', 'string', 'max:30'],
            'fecha_compra' => ['required', 'date', 'before_or_equal:now'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:producto,idProducto'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'items.*.precio_unitario' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'items.*.fecha_vencimiento' => ['required', 'date'],
        ], [
            'items.required' => 'Agregue al menos un producto a la compra.',
        ], [
            'proveedor_id' => 'proveedor', 'fecha_compra' => 'fecha de compra',
            'items.*.producto_id' => 'producto', 'items.*.cantidad' => 'cantidad',
            'items.*.precio_unitario' => 'precio unitario', 'items.*.fecha_vencimiento' => 'fecha de vencimiento',
        ]);

        $compra = $servicio->registrar($datos, $request->user());

        return redirect()->route('compras.show', $compra)->with('ok', 'Mercancía ingresada: se crearon '.$compra->detalles->count().' lote(s) y se actualizó el inventario.');
    }

    public function show(Compra $compra)
    {
        $compra->load('proveedor', 'usuario', 'detalles.producto', 'detalles.lote', 'pagos.usuario');

        return view('compras.show', compact('compra'));
    }
}
