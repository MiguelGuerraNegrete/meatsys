<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Services\ImagenService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF1: gestión de productos cárnicos. */
class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $productos = Producto::with('categoria')
            ->buscar($request->q)
            ->when($request->categoria, fn ($q, $c) => $q->where('Categoria_idCategoria', $c))
            ->when(! $request->boolean('inactivos'), fn ($q) => $q->activos())
            ->orderBy('nombre')->paginate(24)->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'vista' => $request->input('vista', 'tarjetas') === 'lista' ? 'lista' : 'tarjetas',
        ]);
    }

    /** Búsqueda rápida (JSON) para la pantalla de venta y pedidos. */
    public function buscar(Request $request)
    {
        return Producto::with('categoria')->activos()->buscar($request->q)
            ->when($request->categoria, fn ($q, $c) => $q->where('Categoria_idCategoria', $c))
            ->orderBy('nombre')->limit($request->q ? 15 : 60)->get()
            ->map(fn ($p) => [
                'id' => $p->idProducto,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre,
                'tipoCorte' => $p->tipoCorte,
                'unidad' => $p->unidadVenta,
                'precio' => (float) $p->precioVenta,
                'stock' => $p->stockVendible(),
                'foto' => $p->fotoUrl(),
            ]);
    }

    public function create()
    {
        return view('productos.form', ['producto' => new Producto(['activo' => true, 'unidadVenta' => 'kg']), 'categorias' => Categoria::orderBy('nombre')->get()]);
    }

    public function store(Request $request, ImagenService $imagenes)
    {
        $datos = $this->validar($request);
        $datos['stockActual'] = 0; // el stock solo aumenta con compras (lotes)
        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $imagenes->guardar($request->file('imagen'), 'productos');
        }
        Producto::create($datos);

        return redirect()->route('productos.index')->with('ok', 'Producto registrado.');
    }

    public function edit(Producto $producto)
    {
        return view('productos.form', ['producto' => $producto, 'categorias' => Categoria::orderBy('nombre')->get()]);
    }

    public function update(Request $request, Producto $producto, ImagenService $imagenes)
    {
        $datos = $this->validar($request, $producto);
        if ($request->hasFile('imagen')) {
            $imagenes->eliminar($producto->imagen);
            $datos['imagen'] = $imagenes->guardar($request->file('imagen'), 'productos');
        } elseif ($request->boolean('quitar_imagen')) {
            $imagenes->eliminar($producto->imagen);
            $datos['imagen'] = null;
        }
        $producto->update($datos);

        return redirect()->route('productos.index')->with('ok', 'Producto actualizado.');
    }

    /** "Eliminar" marca el producto como inactivo para conservar el historial. */
    public function destroy(Producto $producto)
    {
        $producto->update(['activo' => false]);

        return back()->with('ok', "Producto «{$producto->nombre}» marcado como inactivo.");
    }

    public function activar(Producto $producto)
    {
        $producto->update(['activo' => true]);

        return back()->with('ok', 'Producto activado.');
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        return $request->validate([
            'Categoria_idCategoria' => ['required', 'exists:categoria,idCategoria'],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('producto', 'codigo')->ignore($producto?->idProducto, 'idProducto')],
            'nombre' => ['required', 'string', 'max:100'],
            'tipoCorte' => ['nullable', 'string', 'max:60'],
            'unidadVenta' => ['required', Rule::in(['kg', 'unidad'])],
            'precioVenta' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'stockMinimo' => ['required', 'numeric', 'min:0', 'max:9999999.999'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], ['imagen.max' => 'La foto no debe pesar más de 5 MB.'], ['Categoria_idCategoria' => 'categoría', 'precioVenta' => 'precio de venta', 'stockMinimo' => 'stock mínimo', 'unidadVenta' => 'unidad de venta', 'tipoCorte' => 'tipo de corte']);
    }
}
