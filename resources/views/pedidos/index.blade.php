@extends('layouts.app')
@section('title', 'Pedidos y encargos')
@section('actions')<a href="{{ route('pedidos.create') }}" class="btn btn-primary"><i class="bi bi-bag-plus"></i> Nuevo pedido</a>@endsection
@section('content')
<ul class="nav nav-pills mb-3">
    @foreach(['activos' => 'Por entregar', 'pendiente' => 'Pendientes', 'listo' => 'Listos', 'entregado' => 'Entregados', 'cancelado' => 'Cancelados'] as $k => $l)
        <li class="nav-item"><a class="nav-link {{ $estado === $k ? 'active' : '' }}" href="{{ route('pedidos.index', ['estado' => $k]) }}">{{ $l }}</a></li>
    @endforeach
</ul>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>#</th><th>Cliente</th><th>Entrega</th><th>Productos</th><th class="num">Valor estimado</th><th>Estado</th></tr></thead>
    <tbody>
    @forelse($pedidos as $p)
        <tr onclick="location='{{ route('pedidos.show', $p) }}'" style="cursor:pointer" class="{{ in_array($p->estado, ['pendiente','listo']) && $p->fechaEntrega->isPast() ? 'row-danger' : '' }}">
            <td>{{ $p->idPedido }}</td><td class="fw-semibold">{{ $p->cliente->nombre }}</td>
            <td>{{ $p->fechaEntrega->format('d/m/Y h:i a') }} @if(in_array($p->estado, ['pendiente','listo']) && $p->fechaEntrega->isPast())<span class="badge text-bg-danger">Atrasado</span>@endif</td>
            <td><div class="d-flex align-items-center gap-1">@foreach($p->detalles->take(4) as $d)<img src="{{ $d->producto->fotoUrl() }}" alt="{{ $d->producto->nombre }}" title="{{ $d->producto->nombre }} ({{ cant($d->cantidad, $d->producto->unidadVenta) }})" class="thumb thumb-sm">@endforeach<span class="small ms-1">{{ $p->detalles->map(fn($d) => $d->producto->nombre)->join(', ') }}</span></div></td>
            <td class="num">{{ pesos($p->valorEstimado()) }}</td><td>@include('partials.estado-pedido', ['estado' => $p->estado])</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">No hay pedidos.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $pedidos->links() }}</div>
@endsection
