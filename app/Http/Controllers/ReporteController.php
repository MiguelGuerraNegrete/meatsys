<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** RF10: reportes de ventas, ganancias, cierres e inventario. */
class ReporteController extends Controller
{
    public const TIPOS = [
        'mas-vendidos' => 'Productos más vendidos',
        'ganancias' => 'Ganancias por periodo',
        'cierres' => 'Cierres de caja',
        'inventario' => 'Niveles de inventario',
    ];

    public function index()
    {
        return view('reportes.index', ['tipos' => self::TIPOS]);
    }

    public function show(Request $request, string $tipo)
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404);

        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'categoria' => ['nullable', 'exists:categoria,idCategoria'],
            'producto' => ['nullable', 'exists:producto,idProducto'],
        ]);

        $filtros = [
            'desde' => Carbon::parse($request->input('desde', now()->startOfMonth()->toDateString()))->startOfDay(),
            'hasta' => Carbon::parse($request->input('hasta', now()->toDateString()))->endOfDay(),
            'categoria' => $request->categoria,
            'producto' => $request->producto,
        ];

        $datos = match ($tipo) {
            'mas-vendidos' => $this->masVendidos($filtros),
            'ganancias' => $this->ganancias($filtros),
            'cierres' => $this->cierres($filtros),
            'inventario' => $this->inventario($filtros),
        };

        $vista = [
            'tipo' => $tipo,
            'titulo' => self::TIPOS[$tipo],
            'filtros' => $filtros,
            'datos' => $datos,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'productos' => Producto::orderBy('nombre')->get(['idProducto', 'nombre']),
        ];

        if ($request->boolean('pdf')) {
            return Pdf::loadView('pdf.reporte', $vista)->setPaper('letter')
                ->download('reporte-'.$tipo.'-'.now()->format('Ymd-His').'.pdf');
        }

        return view('reportes.show', $vista);
    }

    /** Base de detalles de venta filtrados. */
    private function detalles(array $f)
    {
        return DB::table('detalle_venta as dv')
            ->join('venta as v', 'v.idVenta', '=', 'dv.Venta_idVenta')
            ->join('producto as p', 'p.idProducto', '=', 'dv.Producto_idProducto')
            ->join('categoria as c', 'c.idCategoria', '=', 'p.Categoria_idCategoria')
            ->join('lote as l', 'l.idLote', '=', 'dv.Lote_idLote')
            ->join('detalle_compra as dc', 'dc.idDetalleCompra', '=', 'l.DetalleCompra_idDetalleCompra')
            ->whereBetween('v.fechaHora', [$f['desde'], $f['hasta']])
            ->when($f['categoria'], fn ($q, $c) => $q->where('p.Categoria_idCategoria', $c))
            ->when($f['producto'], fn ($q, $p) => $q->where('p.idProducto', $p));
    }

    private function masVendidos(array $f): array
    {
        $filas = $this->detalles($f)
            ->select('p.idProducto', 'p.codigo', 'p.nombre', 'p.unidadVenta', 'c.nombre as categoria',
                DB::raw('SUM(dv.cantidad) as cantidad'),
                DB::raw('SUM(dv.subtotal) as ventas'),
                DB::raw('SUM(dv.cantidad * dc.precioUnitario) as costo'),
                DB::raw('COUNT(DISTINCT v.idVenta) as transacciones'))
            ->groupBy('p.idProducto', 'p.codigo', 'p.nombre', 'p.unidadVenta', 'c.nombre')
            ->orderByDesc('ventas')->get()
            ->map(function ($r) {
                $r->costo = round((float) $r->costo, 2);
                $r->ganancia = round((float) $r->ventas - $r->costo, 2);

                return $r;
            });

        $top = $filas->take(10);

        return [
            'filas' => $filas,
            'totales' => ['ventas' => $filas->sum('ventas'), 'costo' => $filas->sum('costo'), 'ganancia' => $filas->sum('ganancia')],
            'grafico' => ['tipo' => 'bar', 'etiquetas' => $top->pluck('nombre'), 'series' => [['nombre' => 'Ventas ($)', 'valores' => $top->pluck('ventas')->map(fn ($v) => round((float) $v, 2))]]],
        ];
    }

    private function ganancias(array $f): array
    {
        $porMes = $f['desde']->diffInDays($f['hasta']) > 62;
        $driver = DB::connection()->getDriverName();
        $expr = $porMes
            ? ($driver === 'sqlite' ? "strftime('%Y-%m', v.fechaHora)" : "DATE_FORMAT(v.fechaHora, '%Y-%m')")
            : 'DATE(v.fechaHora)';

        $filas = $this->detalles($f)
            ->select(DB::raw("$expr as periodo"),
                DB::raw('SUM(dv.subtotal) as ventas'),
                DB::raw('SUM(dv.cantidad * dc.precioUnitario) as costo'),
                DB::raw('COUNT(DISTINCT v.idVenta) as transacciones'))
            ->groupBy('periodo')->orderBy('periodo')->get()
            ->map(function ($r) {
                $r->ventas = round((float) $r->ventas, 2);
                $r->costo = round((float) $r->costo, 2);
                $r->ganancia = round($r->ventas - $r->costo, 2);
                $r->margen = $r->ventas > 0 ? round($r->ganancia / $r->ventas * 100, 1) : 0;

                return $r;
            });

        $ventas = $filas->sum('ventas');
        $ganancia = $filas->sum('ganancia');

        return [
            'porMes' => $porMes,
            'filas' => $filas,
            'totales' => ['ventas' => $ventas, 'costo' => $filas->sum('costo'), 'ganancia' => $ganancia,
                'margen' => $ventas > 0 ? round($ganancia / $ventas * 100, 1) : 0, 'transacciones' => $filas->sum('transacciones')],
            'grafico' => ['tipo' => 'line', 'etiquetas' => $filas->pluck('periodo')->map(fn ($p) => $porMes ? Carbon::createFromFormat('Y-m', $p)->locale('es')->translatedFormat('M Y') : Carbon::parse($p)->format('d/m')), 'series' => [
                ['nombre' => 'Ventas', 'valores' => $filas->pluck('ventas')],
                ['nombre' => 'Ganancia', 'valores' => $filas->pluck('ganancia')],
            ]],
        ];
    }

    private function cierres(array $f): array
    {
        $filas = Caja::with('usuario')->where('estado', 'cerrada')
            ->whereBetween('fechaCierre', [$f['desde'], $f['hasta']])
            ->orderBy('fechaCierre')->get()
            ->map(function ($c) {
                $c->diferencia = round((float) $c->montoContado - (float) $c->saldoFinal, 2);

                return $c;
            });

        return [
            'filas' => $filas,
            'totales' => ['ingresos' => $filas->sum('totalIngresos'), 'egresos' => $filas->sum('totalEgresos'), 'diferencia' => $filas->sum('diferencia')],
            'grafico' => ['tipo' => 'bar', 'etiquetas' => $filas->map(fn ($c) => $c->fechaCierre->format('d/m')), 'series' => [
                ['nombre' => 'Ingresos', 'valores' => $filas->pluck('totalIngresos')->map(fn ($v) => (float) $v)],
                ['nombre' => 'Egresos', 'valores' => $filas->pluck('totalEgresos')->map(fn ($v) => (float) $v)],
            ]],
        ];
    }

    private function inventario(array $f): array
    {
        $filas = Producto::with('categoria')->activos()
            ->when($f['categoria'], fn ($q, $c) => $q->where('Categoria_idCategoria', $c))
            ->when($f['producto'], fn ($q, $p) => $q->where('idProducto', $p))
            ->orderBy('nombre')->get()
            ->map(function ($p) {
                $p->valorizado = round((float) $p->stockActual * (float) $p->precioVenta, 2);
                $p->proximoVenc = $p->lotes()->where('cantidadDisponible', '>', 0)->min('fechaVencimiento');

                return $p;
            });

        $porCategoria = $filas->groupBy(fn ($p) => $p->categoria->nombre)->map(fn ($g) => round($g->sum('valorizado'), 2));

        return [
            'filas' => $filas,
            'totales' => ['valorizado' => $filas->sum('valorizado'), 'bajoMinimo' => $filas->filter->bajoMinimo()->count()],
            'grafico' => ['tipo' => 'bar', 'etiquetas' => $porCategoria->keys(), 'series' => [['nombre' => 'Inventario valorizado a precio de venta ($)', 'valores' => $porCategoria->values()]]],
        ];
    }
}
