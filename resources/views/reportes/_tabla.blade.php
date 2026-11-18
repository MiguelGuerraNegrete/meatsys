{{-- Tabla de cada reporte; se usa en pantalla y en PDF --}}
@if($tipo === 'mas-vendidos')
<table class="table table-sm mb-0">
    <thead><tr><th>#</th><th>Producto</th><th>Categoría</th><th class="num">Cantidad</th><th class="num">Ventas</th><th class="num">Costo</th><th class="num">Ganancia</th></tr></thead>
    <tbody>@forelse($datos['filas'] as $i => $f)
        <tr><td>{{ $i + 1 }}</td><td>{{ $f->nombre }}</td><td>{{ $f->categoria }}</td><td class="num">{{ cant($f->cantidad, $f->unidadVenta) }}</td><td class="num">{{ pesos($f->ventas) }}</td><td class="num">{{ pesos($f->costo) }}</td><td class="num">{{ pesos($f->ganancia) }}</td></tr>
    @empty <tr><td colspan="7">Sin ventas en el periodo.</td></tr> @endforelse</tbody>
    <tfoot><tr><th colspan="4">Total</th><th class="num">{{ pesos($datos['totales']['ventas']) }}</th><th class="num">{{ pesos($datos['totales']['costo']) }}</th><th class="num">{{ pesos($datos['totales']['ganancia']) }}</th></tr></tfoot>
</table>
@elseif($tipo === 'ganancias')
<table class="table table-sm mb-0">
    <thead><tr><th>{{ $datos['porMes'] ? 'Mes' : 'Día' }}</th><th class="num">Ventas</th><th class="num">Transacciones</th><th class="num">Costo</th><th class="num">Ganancia</th><th class="num">Margen</th></tr></thead>
    <tbody>@forelse($datos['filas'] as $f)
        <tr><td>{{ $datos['porMes'] ? $f->periodo : \Carbon\Carbon::parse($f->periodo)->format('d/m/Y') }}</td><td class="num">{{ pesos($f->ventas) }}</td><td class="num">{{ $f->transacciones }}</td><td class="num">{{ pesos($f->costo) }}</td><td class="num">{{ pesos($f->ganancia) }}</td><td class="num">{{ $f->margen }}%</td></tr>
    @empty <tr><td colspan="6">Sin ventas en el periodo.</td></tr> @endforelse</tbody>
    <tfoot><tr><th>Total</th><th class="num">{{ pesos($datos['totales']['ventas']) }}</th><th class="num">{{ $datos['totales']['transacciones'] }}</th><th class="num">{{ pesos($datos['totales']['costo']) }}</th><th class="num">{{ pesos($datos['totales']['ganancia']) }}</th><th class="num">{{ $datos['totales']['margen'] }}%</th></tr></tfoot>
</table>
@elseif($tipo === 'cierres')
<table class="table table-sm mb-0">
    <thead><tr><th>Caja</th><th>Cierre</th><th>Abrió</th><th class="num">Inicial</th><th class="num">Ingresos</th><th class="num">Egresos</th><th class="num">Esperado</th><th class="num">Contado</th><th class="num">Diferencia</th></tr></thead>
    <tbody>@forelse($datos['filas'] as $c)
        <tr><td>#{{ $c->idCaja }}</td><td>{{ $c->fechaCierre->format('d/m/Y h:i a') }}</td><td>{{ $c->usuario->nombres }}</td><td class="num">{{ pesos($c->montoInicial) }}</td><td class="num">{{ pesos($c->totalIngresos) }}</td><td class="num">{{ pesos($c->totalEgresos) }}</td><td class="num">{{ pesos($c->saldoFinal) }}</td><td class="num">{{ pesos($c->montoContado) }}</td><td class="num">{{ pesos($c->diferencia) }}</td></tr>
    @empty <tr><td colspan="9">Sin cierres en el periodo.</td></tr> @endforelse</tbody>
    <tfoot><tr><th colspan="4">Total</th><th class="num">{{ pesos($datos['totales']['ingresos']) }}</th><th class="num">{{ pesos($datos['totales']['egresos']) }}</th><th colspan="2"></th><th class="num">{{ pesos($datos['totales']['diferencia']) }}</th></tr></tfoot>
</table>
@else
<table class="table table-sm mb-0">
    <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th class="num">Stock</th><th class="num">Mínimo</th><th>Próx. vencimiento</th><th class="num">Valorizado</th></tr></thead>
    <tbody>@foreach($datos['filas'] as $p)
        <tr class="{{ $p->bajoMinimo() ? 'row-alert' : '' }}"><td>{{ $p->codigo }}</td><td>{{ $p->nombre }}{{ $p->bajoMinimo() ? ' ⚠' : '' }}</td><td>{{ $p->categoria->nombre }}</td><td class="num">{{ cant($p->stockActual, $p->unidadVenta) }}</td><td class="num">{{ cant($p->stockMinimo, $p->unidadVenta) }}</td>
            <td>{{ $p->proximoVenc ? \Carbon\Carbon::parse($p->proximoVenc)->format('d/m/Y') : '—' }}</td><td class="num">{{ pesos($p->valorizado) }}</td></tr>
    @endforeach</tbody>
    <tfoot><tr><th colspan="6">Total valorizado a precio de venta · {{ $datos['totales']['bajoMinimo'] }} producto(s) bajo el mínimo</th><th class="num">{{ pesos($datos['totales']['valorizado']) }}</th></tr></tfoot>
</table>
@endif
