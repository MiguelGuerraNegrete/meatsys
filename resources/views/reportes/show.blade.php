@extends('layouts.app')
@section('title', $titulo)
@section('subtitle', $tipo === 'inventario' ? 'Situación actual' : 'Del '.$filtros['desde']->format('d/m/Y').' al '.$filtros['hasta']->format('d/m/Y'))
@section('actions')
    <a href="{{ request()->fullUrlWithQuery(['pdf' => 1]) }}" class="btn btn-primary"><i class="bi bi-download"></i> Descargar PDF</a>
    <a href="{{ route('reportes.index') }}" class="btn btn-light">Reportes</a>
@endsection
@section('content')
<form class="card mb-3" method="GET"><div class="card-body row g-2 align-items-end">
    @if($tipo !== 'inventario')
    <div class="col-md-2"><label class="form-label small">Desde</label><input type="date" name="desde" value="{{ $filtros['desde']->toDateString() }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label small">Hasta</label><input type="date" name="hasta" value="{{ $filtros['hasta']->toDateString() }}" class="form-control"></div>
    @endif
    @if($tipo !== 'cierres')
    <div class="col-md-3"><label class="form-label small">Categoría</label><select name="categoria" class="form-select"><option value="">Todas</option>@foreach($categorias as $c)<option value="{{ $c->idCategoria }}" @selected($filtros['categoria'] == $c->idCategoria)>{{ $c->nombre }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">Producto</label><select name="producto" class="form-select"><option value="">Todos</option>@foreach($productos as $p)<option value="{{ $p->idProducto }}" @selected($filtros['producto'] == $p->idProducto)>{{ $p->nombre }}</option>@endforeach</select></div>
    @endif
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Generar</button></div>
</div></form>

@if($tipo === 'ganancias')
<div class="row g-3 mb-3">
    <div class="col-sm-3"><div class="card stat"><div class="label">Ventas</div><div class="value">{{ pesos($datos['totales']['ventas']) }}</div></div></div>
    <div class="col-sm-3"><div class="card stat"><div class="label">Costo de lo vendido</div><div class="value">{{ pesos($datos['totales']['costo']) }}</div></div></div>
    <div class="col-sm-3"><div class="card stat"><div class="label">Ganancia bruta</div><div class="value text-success">{{ pesos($datos['totales']['ganancia']) }}</div></div></div>
    <div class="col-sm-3"><div class="card stat"><div class="label">Margen</div><div class="value">{{ $datos['totales']['margen'] }}%</div></div></div>
</div>
@endif

<div class="card mb-3"><div class="card-body"><canvas id="grafico" height="90"></canvas></div></div>
<div class="card"><div class="table-responsive">@include('reportes._tabla')</div></div>
@if(in_array($tipo, ['mas-vendidos','ganancias']))<p class="small text-muted mt-2">El costo se calcula con el precio de compra del lote del que se descontó cada venta.</p>@endif
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
<script>
const g = @json($datos['grafico']);
const colores = ['#8f1d2c', '#d08a3a', '#4f7a5a'];
new Chart(document.getElementById('grafico'), {
    type: g.tipo,
    data: { labels: g.etiquetas, datasets: g.series.map((s, i) => ({ label: s.nombre, data: s.valores, backgroundColor: colores[i], borderColor: colores[i], borderRadius: 5, maxBarThickness: 48, cubicInterpolationMode: 'monotone', fill: false })) },
    options: { plugins: { legend: { display: g.series.length > 1 }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + MS.pesos(c.parsed.y) } } },
               scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + Number(v).toLocaleString('es-CO') } }, x: { grid: { display: false } } } }
});
</script>
@endpush
