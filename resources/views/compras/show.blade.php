@extends('layouts.app')
@section('title', 'Compra #'.$compra->idCompra)
@section('subtitle', $compra->proveedor->razonSocial.' · '.$compra->fechaCompra->format('d/m/Y h:i a').' · registró '.$compra->usuario->nombre_completo)
@section('actions')
    @if(auth()->user()->esAdmin() && $compra->saldoPendiente > 0)<a href="{{ route('pagos.create', $compra) }}" class="btn btn-primary"><i class="bi bi-credit-card"></i> Registrar pago</a>@endif
    <a href="{{ route('compras.index') }}" class="btn btn-light">Volver</a>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-3"><div class="card stat"><div class="label">Factura proveedor</div><div class="value fs-5">{{ $compra->numeroFactura ?: '—' }}</div></div></div>
    <div class="col-sm-3"><div class="card stat"><div class="label">Total</div><div class="value">{{ pesos($compra->total) }}</div></div></div>
    <div class="col-sm-3"><div class="card stat"><div class="label">Saldo pendiente</div><div class="value {{ $compra->saldoPendiente > 0 ? 'text-danger' : '' }}">{{ pesos($compra->saldoPendiente) }}</div></div></div>
    <div class="col-sm-3"><div class="card stat"><div class="label">Estado de pago</div><div class="value fs-5">@include('partials.estado-pago', ['estado' => $compra->estadoPago])</div></div></div>
</div>
<div class="card mb-3"><div class="card-header">Productos y lotes</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Producto</th><th class="num">Cantidad</th><th class="num">Precio unit.</th><th class="num">Subtotal</th><th>Lote</th><th>Vence</th><th class="num">Disponible</th></tr></thead>
    <tbody>
    @foreach($compra->detalles as $d)
        <tr><td><div class="con-foto"><img src="{{ $d->producto->fotoUrl() }}" alt="" class="thumb thumb-sm"><span class="fw-semibold">{{ $d->producto->nombre }}</span></div></td><td class="num">{{ cant($d->cantidad, $d->producto->unidadVenta) }}</td><td class="num">{{ pesos($d->precioUnitario) }}</td><td class="num">{{ pesos($d->subtotal) }}</td>
            <td>#{{ $d->lote?->idLote }}</td><td>{{ $d->lote?->fechaVencimiento->format('d/m/Y') }}</td><td class="num">{{ cant($d->lote?->cantidadDisponible, $d->producto->unidadVenta) }}</td></tr>
    @endforeach
    </tbody>
</table></div></div>
<div class="card"><div class="card-header">Pagos realizados</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Fecha</th><th>Método</th><th>Registró</th><th class="num">Monto</th></tr></thead>
    <tbody>
    @forelse($compra->pagos as $p)
        <tr><td>{{ $p->fechaPago->format('d/m/Y h:i a') }}</td><td>{{ ucfirst($p->metodoPago) }}</td><td>{{ $p->usuario->nombre_completo }}</td><td class="num">{{ pesos($p->monto) }}</td></tr>
    @empty
        <tr><td colspan="4" class="text-muted">Sin pagos registrados.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
