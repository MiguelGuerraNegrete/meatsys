<?php

namespace App\Http\Controllers;

use App\Models\AlertaVencimiento;
use App\Services\AlertaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF6: alertas de vencimiento. */
class AlertaController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->input('estado', 'pendiente');
        $alertas = AlertaVencimiento::with('lote.producto.categoria')
            ->where('estado', $estado)
            ->orderBy($estado === 'pendiente' ? 'diasRestantes' : 'fechaGeneracion', $estado === 'pendiente' ? 'asc' : 'desc')
            ->paginate(25)->withQueryString();

        return view('alertas.index', compact('alertas', 'estado'));
    }

    public function generar(AlertaService $servicio)
    {
        $n = $servicio->generar();

        return back()->with('ok', "Revisión de lotes completada: {$n} alerta(s) pendiente(s).");
    }

    public function atender(Request $request, AlertaVencimiento $alerta, AlertaService $servicio)
    {
        $request->validate([
            'accion' => ['required', Rule::in(['promoción', 'retiro', 'otra'])],
            'detalle' => ['nullable', 'string', 'max:150'],
        ]);
        if ($alerta->estado === 'atendida') {
            return back()->withErrors(['alerta' => 'Esta alerta ya fue atendida.']);
        }
        $servicio->atender($alerta, $request->accion, $request->detalle);

        return back()->with('ok', 'Alerta marcada como atendida'.($request->accion === 'retiro' ? ' y stock ajustado.' : '.'));
    }
}
