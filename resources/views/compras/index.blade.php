@extends('layouts.app')
@section('title', 'Compras e ingreso de mercancía')
@section('actions')<a href="{{ route('compras.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva compra</a>@endsection
@section('content')
<form class="d-flex gap-2 mb-3 flex-wrap" method="GET">
    <select name="proveedor" class="form-select" style="max-width:260px"><option value="">Todos los proveedores</option>
        @foreach($proveedores as $p)<option value="{{ $p->idProveedor }}" @selected(request('proveedor') == $p->idProveedor)>{{ $p->razonSocial }}</option>@endforeach</select>
    <select name="estado" class="form-select" style="max-width:180px"><option value="">Todos los estados</option>
        @foreach(['pendiente','parcial','pagado'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ ucfirst($e) }}</option>@endforeach</select>
    <button class="btn btn-outline-secondary">Filtrar</button>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Factura</th><th>Registró</th><th class="num">Total</th><th class="num">Saldo</th><th>Pago</th></tr></thead>
    <tbody>
    @forelse($compras as $c)
        <tr onclick="location='{{ route('compras.show', $c) }}'" style="cursor:pointer">
            <td>{{ $c->idCompra }}</td><td>{{ $c->fechaCompra->format('d/m/Y h:i a') }}</td><td class="fw-semibold">{{ $c->proveedor->razonSocial }}</td>
            <td>{{ $c->numeroFactura }}</td><td>{{ $c->usuario->nombres }}</td><td class="num">{{ pesos($c->total) }}</td><td class="num">{{ pesos($c->saldoPendiente) }}</td>
            <td>@include('partials.estado-pago', ['estado' => $c->estadoPago])</td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-muted">No hay compras registradas.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $compras->links() }}</div>
@endsection
