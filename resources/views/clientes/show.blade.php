@extends('layouts.app')
@section('title', $cliente->nombre)
@section('subtitle', 'Doc. '.$cliente->documento.($cliente->telefono ? ' · Tel. '.$cliente->telefono : '').($cliente->email ? ' · '.$cliente->email : '').' · cliente desde '.$cliente->fechaRegistro->format('d/m/Y'))
@section('actions')
    <a href="{{ route('pedidos.create', ['cliente' => $cliente->idCliente]) }}" class="btn btn-primary"><i class="bi bi-bag-plus"></i> Nuevo pedido</a>
    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Modificar</a>
@endsection
@section('content')
<div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Compras <span class="text-muted fw-normal">· total {{ pesos($cliente->ventas->sum('total')) }}</span></div><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Factura</th><th>Fecha</th><th class="num">Total</th></tr></thead>
        <tbody>
        @forelse($cliente->ventas as $v)
            <tr onclick="location='{{ route('ventas.show', $v) }}'" style="cursor:pointer"><td>{{ $v->factura?->numeroFactura }}</td><td>{{ $v->fechaHora->format('d/m/Y h:i a') }}</td><td class="num">{{ pesos($v->total) }}</td></tr>
        @empty <tr><td colspan="3" class="text-muted">Sin compras.</td></tr> @endforelse
        </tbody></table></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Pedidos</div><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Entrega</th><th>Productos</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($cliente->pedidos as $p)
            <tr onclick="location='{{ route('pedidos.show', $p) }}'" style="cursor:pointer"><td>{{ $p->idPedido }}</td><td>{{ $p->fechaEntrega->format('d/m/Y h:i a') }}</td>
                <td class="small">{{ $p->detalles->map(fn($d) => $d->producto->nombre)->join(', ') }}</td><td>@include('partials.estado-pedido', ['estado' => $p->estado])</td></tr>
        @empty <tr><td colspan="4" class="text-muted">Sin pedidos.</td></tr> @endforelse
        </tbody></table></div></div></div>
</div>
@endsection
