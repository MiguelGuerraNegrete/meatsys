<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $proveedores = Proveedor::withSum('compras as saldo', 'saldoPendiente')->withCount('compras')
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('razonSocial', 'like', "%$t%")->orWhere('nit', 'like', "%$t%")))
            ->orderByDesc('activo')->orderBy('razonSocial')->paginate(15)->withQueryString();

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.form', ['proveedor' => new Proveedor]);
    }

    public function store(Request $request)
    {
        Proveedor::create($this->validar($request) + ['activo' => true]);

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor registrado.');
    }

    public function show(Proveedor $proveedor)
    {
        $proveedor->load(['compras' => fn ($q) => $q->latest('fechaCompra'), 'compras.pagos']);

        return view('proveedores.show', compact('proveedor'));
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.form', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $proveedor->update($this->validar($request, $proveedor) + ['activo' => $request->boolean('activo')]);

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor actualizado.');
    }

    public function destroy(Proveedor $proveedor)
    {
        if ($proveedor->compras()->where('saldoPendiente', '>', 0)->exists()) {
            return back()->withErrors(['proveedor' => 'No se puede eliminar: el proveedor tiene compras con saldo pendiente.']);
        }

        if ($proveedor->compras()->exists()) {
            // Con historial de compras no se borra físicamente para no romper la trazabilidad de lotes.
            $proveedor->update(['activo' => false]);

            return back()->with('ok', 'El proveedor tiene compras registradas, por eso se desactivó en lugar de eliminarse.');
        }

        $proveedor->delete();

        return back()->with('ok', 'Proveedor eliminado.');
    }

    private function validar(Request $request, ?Proveedor $p = null): array
    {
        return $request->validate([
            'nit' => ['required', 'string', 'max:20', Rule::unique('proveedor', 'nit')->ignore($p?->idProveedor, 'idProveedor')],
            'razonSocial' => ['required', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:80'],
            'direccion' => ['nullable', 'string', 'max:150'],
        ], ['nit.unique' => 'Ya existe un proveedor con ese NIT.'], ['razonSocial' => 'razón social']);
    }
}
