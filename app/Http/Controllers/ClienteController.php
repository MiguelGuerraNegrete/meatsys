<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF8: clientes frecuentes. */
class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $clientes = Cliente::withCount('ventas')
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('nombre', 'like', "%$t%")->orWhere('documento', 'like', "%$t%")->orWhere('telefono', 'like', "%$t%")))
            ->orderBy('nombre')->paginate(15)->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function buscar(Request $request)
    {
        return Cliente::when($request->q, fn ($q, $t) => $q->where('nombre', 'like', "%$t%")->orWhere('documento', 'like', "%$t%"))
            ->orderBy('nombre')->limit(10)->get(['idCliente as id', 'documento', 'nombre', 'email']);
    }

    public function create()
    {
        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $request->validate(['autorizacion' => ['accepted']], ['autorizacion.accepted' => 'Debe contar con la autorización del cliente para el tratamiento de sus datos (Ley 1581 de 2012).']);
        $cliente = Cliente::create($datos + ['fechaRegistro' => now()->toDateString()]);

        if ($request->filled('volver')) {
            return redirect($request->volver)->with('ok', 'Cliente registrado.');
        }

        return redirect()->route('clientes.show', $cliente)->with('ok', 'Cliente registrado.');
    }

    public function show(Cliente $cliente)
    {
        $cliente->load([
            'ventas' => fn ($q) => $q->latest('fechaHora')->limit(50), 'ventas.factura',
            'pedidos' => fn ($q) => $q->latest('fechaPedido')->limit(50), 'pedidos.detalles.producto',
        ]);

        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.form', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('clientes.show', $cliente)->with('ok', 'Cliente actualizado.');
    }

    private function validar(Request $request, ?Cliente $c = null): array
    {
        return $request->validate([
            'documento' => ['required', 'string', 'max:15', Rule::unique('cliente', 'documento')->ignore($c?->idCliente, 'idCliente')],
            'nombre' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:80'],
            'direccion' => ['nullable', 'string', 'max:150'],
        ], ['documento.unique' => 'Ya existe un cliente con ese documento.']);
    }
}
