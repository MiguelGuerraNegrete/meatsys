<?php

namespace App\Http\Controllers;

use App\Models\AlertaVencimiento;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $hoy = now()->toDateString();

        return view('dashboard', [
            'caja' => Caja::abierta(),
            'ventasHoy' => Venta::whereDate('fechaHora', $hoy)->count(),
            'totalHoy' => (float) Venta::whereDate('fechaHora', $hoy)->sum('total'),
            'alertasPendientes' => AlertaVencimiento::where('estado', 'pendiente')->count(),
            'bajoMinimo' => Producto::activos()->whereColumn('stockActual', '<=', 'stockMinimo')->count(),
            'pedidosPendientes' => Pedido::whereIn('estado', ['pendiente', 'listo'])->count(),
            'pedidosProximos' => Pedido::with('cliente')->whereIn('estado', ['pendiente', 'listo'])->orderBy('fechaEntrega')->limit(5)->get(),
            'porPagar' => (float) Compra::sum('saldoPendiente'),
            'ultimasVentas' => Venta::with('cliente', 'factura')->latest('fechaHora')->limit(6)->get(),
            'categorias' => Categoria::withCount(['productos' => fn ($q) => $q->where('activo', true)])->orderBy('nombre')->get(),
            'masVendidos' => Producto::with('categoria')
                ->joinSub(
                    DB::table('detalle_venta as dv')->join('venta as v', 'v.idVenta', '=', 'dv.Venta_idVenta')
                        ->where('v.fechaHora', '>=', now()->subDays(6)->startOfDay())
                        ->groupBy('dv.Producto_idProducto')
                        ->select('dv.Producto_idProducto', DB::raw('SUM(dv.cantidad) as vendido'), DB::raw('SUM(dv.subtotal) as ingresos')),
                    'mv', 'mv.Producto_idProducto', '=', 'producto.idProducto')
                ->orderByDesc('mv.ingresos')->limit(5)->get(['producto.*', 'mv.vendido', 'mv.ingresos']),
            'listaBajoMinimo' => Producto::with('categoria')->activos()->whereColumn('stockActual', '<=', 'stockMinimo')
                ->orderByRaw('stockActual - stockMinimo')->limit(5)->get(),
            'ventas7' => Venta::select(DB::raw('DATE(fechaHora) as dia'), DB::raw('SUM(total) as total'))
                ->where('fechaHora', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('dia')->pluck('total', 'dia'),
        ]);
    }
}
