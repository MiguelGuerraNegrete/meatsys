@extends('layouts.app')
@php($rol = auth()->user()->rol->nombre)
@php($vende = in_array($rol, ['administrador','cajero']))
@php($bodega = in_array($rol, ['administrador','inventario']))
@section('title', 'Panel principal')
@section('subtitle', ucfirst(now()->locale('es')->translatedFormat('l j \d\e F')))
@section('content')

{{-- Portada --}}
<div class="hero mb-3" style="background-image:url('{{ asset('img/portada/panel.jpg') }}')">
    <div class="hero-body">
        <h2>Hola, {{ auth()->user()->nombres }}</h2>
        <p class="mb-3">
            @if($vende)
                @if($caja) Caja abierta desde las {{ $caja->fechaApertura->format('h:i a') }} · {{ $ventasHoy }} ventas hoy por {{ pesos($totalHoy) }}
                @else La caja está cerrada: ábrela para empezar a vender. @endif
            @else
                {{ $alertasPendientes }} alerta(s) de vencimiento y {{ $bajoMinimo }} producto(s) por reabastecer.
            @endif
        </p>
        <div class="d-flex gap-2 flex-wrap">
            @if($vende)
                @if($caja)
                    <a href="{{ route('ventas.create') }}" class="btn btn-light"><i class="bi bi-cart-plus"></i> Nueva venta</a>
                @else
                    <a href="{{ route('caja.index') }}" class="btn btn-light"><i class="bi bi-unlock"></i> Abrir caja</a>
                @endif
                <a href="{{ route('pedidos.create') }}" class="btn btn-outline-light"><i class="bi bi-bag-plus"></i> Nuevo pedido</a>
            @endif
            @if($bodega)
                <a href="{{ route('compras.create') }}" class="btn {{ $vende ? 'btn-outline-light' : 'btn-light' }}"><i class="bi bi-truck"></i> Ingresar mercancía</a>
            @endif
        </div>
    </div>
</div>

{{-- Indicadores --}}
<div class="row g-3 mb-3">
    @if($vende)
    <div class="col-sm-6 col-xl"><div class="card stat h-100"><div class="d-flex justify-content-between"><div><div class="label">Ventas de hoy</div><div class="value">{{ pesos($totalHoy) }}</div><div class="small text-muted">{{ $ventasHoy }} transacciones</div></div><div class="icon"><i class="bi bi-cash-coin"></i></div></div></div></div>
    <div class="col-sm-6 col-xl"><a href="{{ route('pedidos.index') }}" class="text-decoration-none text-reset h-100 d-block"><div class="card stat h-100"><div class="d-flex justify-content-between"><div><div class="label">Pedidos por entregar</div><div class="value">{{ $pedidosPendientes }}</div><div class="small text-muted">pendientes o listos</div></div><div class="icon"><i class="bi bi-bag-check"></i></div></div></div></a></div>
    @endif
    @if($bodega)
    <div class="col-sm-6 col-xl"><a href="{{ route('alertas.index') }}" class="text-decoration-none text-reset h-100 d-block"><div class="card stat h-100"><div class="d-flex justify-content-between"><div><div class="label">Alertas de vencimiento</div><div class="value {{ $alertasPendientes ? 'text-warning' : '' }}">{{ $alertasPendientes }}</div><div class="small text-muted">lotes por atender</div></div><div class="icon"><i class="bi bi-alarm"></i></div></div></div></a></div>
    <div class="col-sm-6 col-xl"><a href="{{ route('inventario.index', ['bajo' => 1]) }}" class="text-decoration-none text-reset h-100 d-block"><div class="card stat h-100"><div class="d-flex justify-content-between"><div><div class="label">Bajo stock mínimo</div><div class="value {{ $bajoMinimo ? 'text-danger' : '' }}">{{ $bajoMinimo }}</div><div class="small text-muted">productos a reabastecer</div></div><div class="icon"><i class="bi bi-box-seam"></i></div></div></div></a></div>
    @endif
    @if($rol === 'administrador')
    <div class="col-sm-6 col-xl"><a href="{{ route('pagos.index') }}" class="text-decoration-none text-reset h-100 d-block"><div class="card stat h-100"><div class="d-flex justify-content-between"><div><div class="label">Por pagar a proveedores</div><div class="value">{{ pesos($porPagar) }}</div><div class="small text-muted">saldo pendiente</div></div><div class="icon"><i class="bi bi-building"></i></div></div></div></a></div>
    @endif
</div>

{{-- Categorías con foto --}}
@if($categorias->count())
<div class="d-flex align-items-end mb-2">
    <h2 class="h6 fw-bold mb-0">Nuestros productos</h2>
    <a href="{{ route('productos.index') }}" class="ms-auto small">Ver catálogo completo</a>
</div>
<div class="row g-3 mb-4">
    @foreach($categorias as $c)
        <div class="col-6 col-md-4 col-xl">
            <a href="{{ route('productos.index', ['categoria' => $c->idCategoria]) }}" class="cat-tile">
                <img src="{{ $c->fotoUrl() }}" alt="{{ $c->nombre }}" loading="lazy">
                <div class="cat-info"><div class="cat-nombre">{{ $c->nombre }}</div><div class="cat-sub">{{ $c->productos_count }} producto(s)</div></div>
            </a>
        </div>
    @endforeach
</div>
@endif

<div class="row g-3">
    @if($vende)
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">Ventas de los últimos 7 días</div>
            <div class="card-body"><canvas id="graf7" height="130"></canvas></div>
        </div>
    </div>
    @endif
    <div class="{{ $vende ? 'col-lg-5' : 'col-lg-6' }}">
        <div class="card h-100">
            <div class="card-header">Más vendidos esta semana</div>
            <ul class="list-group list-group-flush">
                @forelse($masVendidos as $i => $p)
                    <li class="list-group-item con-foto">
                        <img src="{{ $p->fotoUrl() }}" alt="" class="thumb">
                        <div class="flex-grow-1"><div class="fw-semibold text-truncate">{{ $p->nombre }}</div><div class="small text-muted">{{ cant($p->vendido, $p->unidadVenta) }} vendidos</div></div>
                        <div class="text-end fw-semibold">{{ pesos($p->ingresos) }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">Aún no hay ventas esta semana.</li>
                @endforelse
            </ul>
        </div>
    </div>
    @if($bodega)
    <div class="{{ $vende ? 'col-lg-5' : 'col-lg-6' }}">
        <div class="card h-100">
            <div class="card-header d-flex">Por reabastecer <a href="{{ route('compras.create') }}" class="ms-auto small">Ingresar mercancía</a></div>
            <ul class="list-group list-group-flush">
                @forelse($listaBajoMinimo as $p)
                    <li class="list-group-item con-foto">
                        <img src="{{ $p->fotoUrl() }}" alt="" class="thumb">
                        <div class="flex-grow-1"><div class="fw-semibold text-truncate">{{ $p->nombre }}</div><div class="small text-muted">Mínimo {{ cant($p->stockMinimo, $p->unidadVenta) }}</div></div>
                        <span class="badge text-bg-warning">{{ cant($p->stockActual, $p->unidadVenta) }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted small"><i class="bi bi-check-circle text-success"></i> Todo el inventario está sobre el mínimo.</li>
                @endforelse
            </ul>
        </div>
    </div>
    @endif
    @if($vende)
    <div class="{{ $bodega ? 'col-lg-7' : 'col-lg-6' }}">
        <div class="card h-100">
            <div class="card-header d-flex">Próximos pedidos <a href="{{ route('pedidos.create') }}" class="ms-auto small">Nuevo</a></div>
            <ul class="list-group list-group-flush">
                @forelse($pedidosProximos as $p)
                    <a href="{{ route('pedidos.show', $p) }}" class="list-group-item list-group-item-action d-flex">
                        <div><div class="fw-semibold">{{ $p->cliente->nombre }}</div><div class="small text-muted">Entrega {{ $p->fechaEntrega->format('d/m h:i a') }}</div></div>
                        <span class="badge {{ $p->estado === 'listo' ? 'text-bg-success' : 'text-bg-secondary' }} ms-auto align-self-center">{{ ucfirst($p->estado) }}</span>
                    </a>
                @empty
                    <li class="list-group-item text-muted small">No hay pedidos pendientes.</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header">Últimas ventas</div>
            <div class="table-responsive"><table class="table table-hover mb-0">
                <thead><tr><th>Factura</th><th>Fecha</th><th>Cliente</th><th>Pago</th><th class="num">Total</th></tr></thead>
                <tbody>
                @forelse($ultimasVentas as $v)
                    <tr onclick="location='{{ route('ventas.show', $v) }}'" style="cursor:pointer">
                        <td class="fw-semibold">{{ $v->factura?->numeroFactura }}</td><td>{{ $v->fechaHora->format('d/m/Y h:i a') }}</td>
                        <td>{{ $v->cliente?->nombre ?? 'Consumidor final' }}</td><td>{{ ucfirst($v->metodoPago) }}</td><td class="num">{{ pesos($v->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Aún no hay ventas registradas.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
    @endif
</div>
@endsection
@push('scripts')
@if($vende)
<script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
<script>
const dias = [], valores = [], datos = @json($ventas7);
for (let i = 6; i >= 0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const k = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    dias.push(d.toLocaleDateString('es-CO', { weekday: 'short', day: 'numeric' }));
    valores.push(Number(datos[k] || 0));
}
new Chart(document.getElementById('graf7'), {
    type: 'bar',
    data: { labels: dias, datasets: [{ label: 'Ventas', data: valores, backgroundColor: '#8f1d2c', borderRadius: 6, maxBarThickness: 42 }] },
    options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => MS.pesos(c.parsed.y) } } },
               scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + Number(v).toLocaleString('es-CO') } }, x: { grid: { display: false } } } }
});
</script>
@endif
@endpush
