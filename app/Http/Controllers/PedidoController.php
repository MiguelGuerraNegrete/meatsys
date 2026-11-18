<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** RF8: pedidos o encargos especiales. */
class PedidoController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado', 'activos');
        $pedidos = Pedido::with('cliente', 'detalles.producto')
            ->when($estado === 'activos', fn ($q) => $q->whereIn('estado', ['pendiente', 'listo']))
            ->when(in_array($estado, Pedido::ESTADOS), fn ($q) => $q->where('estado', $estado))
            ->orderBy($estado === 'activos' ? 'fechaEntrega' : 'fechaPedido', $estado === 'activos' ? 'asc' : 'desc')
            ->paginate(15)->withQueryString();

        return view('pedidos.index', compact('pedidos', 'estado'));
    }

    public function create(Request $request)
    {
        return view('pedidos.create', ['cliente' => $request->cliente ? Cliente::find($request->cliente) : null]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => ['required', 'exists:cliente,idCliente'],
            'fechaEntrega' => ['required', 'date', 'after:now'],
            'observaciones' => ['nullable', 'string', 'max:300'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'exists:producto,idProducto'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
        ], ['items.required' => 'Agregue al menos un producto al pedido.'], ['cliente_id' => 'cliente', 'fechaEntrega' => 'fecha de entrega']);

        $pedido = DB::transaction(function () use ($datos, $request) {
            $pedido = Pedido::create([
                'Cliente_idCliente' => $datos['cliente_id'],
                'Usuario_idUsuario' => $request->user()->idUsuario,
                'fechaPedido' => now(),
                'fechaEntrega' => $datos['fechaEntrega'],
                'estado' => 'pendiente',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
            foreach ($datos['items'] as $item) {
                $pedido->detalles()->create(['Producto_idProducto' => $item['producto_id'], 'cantidad' => round((float) $item['cantidad'], 3)]);
            }

            return $pedido;
        });

        return redirect()->route('pedidos.show', $pedido)->with('ok', 'Pedido registrado con estado «Pendiente».');
    }

    public function show(Pedido $pedido)
    {
        $pedido->load('cliente', 'usuario', 'detalles.producto');

        return view('pedidos.show', compact('pedido'));
    }

    public function estado(Request $request, Pedido $pedido)
    {
        $request->validate(['estado' => ['required', Rule::in(['pendiente', 'listo', 'entregado', 'cancelado'])]]);

        if (in_array($pedido->estado, ['entregado', 'cancelado'])) {
            return back()->withErrors(['estado' => 'El pedido ya está '.$pedido->estado.' y no puede cambiar de estado.']);
        }
        $pedido->update(['estado' => $request->estado]);

        return back()->with('ok', 'Pedido marcado como «'.ucfirst($request->estado).'».');
    }
}
