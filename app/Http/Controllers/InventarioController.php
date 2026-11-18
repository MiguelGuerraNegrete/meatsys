<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Http\Request;

/** Consultar niveles de inventario y lotes. */
class InventarioController extends Controller
{
    public function index(Request $request)
    {
        $productos = Producto::with('categoria')->activos()
            ->buscar($request->q)
            ->when($request->categoria, fn ($q, $c) => $q->where('Categoria_idCategoria', $c))
            ->when($request->boolean('bajo'), fn ($q) => $q->whereColumn('stockActual', '<=', 'stockMinimo'))
            ->orderByRaw('CASE WHEN stockActual <= stockMinimo THEN 0 ELSE 1 END')
            ->orderBy('nombre')->get();

        return view('inventario.index', ['productos' => $productos, 'categorias' => Categoria::orderBy('nombre')->get()]);
    }

    public function lotes(Producto $producto)
    {
        $lotes = Lote::with('detalleCompra.compra.proveedor')->where('Producto_idProducto', $producto->idProducto)
            ->orderByDesc('cantidadDisponible')->orderBy('fechaVencimiento')->paginate(30);

        return view('inventario.lotes', compact('producto', 'lotes'));
    }
}
