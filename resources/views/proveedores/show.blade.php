@extends('layouts.app')
@section('title', $proveedor->razonSocial)
@section('subtitle', 'NIT '.$proveedor->nit.($proveedor->telefono ? ' · Tel. '.$proveedor->telefono : '').($proveedor->email ? ' · '.$proveedor->email : ''))
@section('actions')<a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Modificar</a>@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="card stat"><div class="label">Total comprado</div><div class="value">{{ pesos($proveedor->compras->sum('total')) }}</div></div></div>
    <div class="col-sm-4"><div class="card stat"><div class="label">Pagado</div><div class="value">{{ pesos($proveedor->compras->sum('total') - $proveedor->compras->sum('saldoPendiente')) }}</div></div></div>
    <div class="col-sm-4"><div class="card stat"><div class="label">Saldo pendiente</div><div class="value text-danger">{{ pesos($proveedor->compras->sum('saldoPendiente')) }}</div></div></div>
</div>
<div class="card"><div class="card-header">Compras</div><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>#</th><th>Fecha</th><th>Factura proveedor</th><th class="num">Total</th><th class="num">Saldo</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($proveedor->compras as $c)
        <tr><td>{{ $c->idCompra }}</td><td>{{ $c->fechaCompra->format('d/m/Y') }}</td><td>{{ $c->numeroFactura }}</td><td class="num">{{ pesos($c->total) }}</td><td class="num">{{ pesos($c->saldoPendiente) }}</td>
            <td>@include('partials.estado-pago', ['estado' => $c->estadoPago])</td>
            <td class="text-end"><a href="{{ route('compras.show', $c) }}" class="btn btn-sm btn-light">Ver</a> @if($c->saldoPendiente > 0)<a href="{{ route('pagos.create', $c) }}" class="btn btn-sm btn-primary">Pagar</a>@endif</td></tr>
    @empty
        <tr><td colspan="7" class="text-muted">Sin compras.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
