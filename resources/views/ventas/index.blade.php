@extends('layouts.app')
@section('title', 'Ventas y facturas')
@section('actions')<a href="{{ route('ventas.create') }}" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nueva venta</a>@endsection
@section('content')
<form class="row g-2 mb-3" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="N.º factura o cliente"></div>
    <div class="col-md-2"><input type="date" name="desde" value="{{ request('desde') }}" class="form-control" title="Desde"></div>
    <div class="col-md-2"><input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control" title="Hasta"></div>
    <div class="col-md-2"><select name="metodo" class="form-select"><option value="">Todo método</option>@foreach(['efectivo','tarjeta','transferencia'] as $m)<option value="{{ $m }}" @selected(request('metodo') === $m)>{{ ucfirst($m) }}</option>@endforeach</select></div>
    <div class="col-md-3 d-flex gap-2"><button class="btn btn-outline-secondary">Filtrar</button>@if(request()->query())<a href="{{ route('ventas.index') }}" class="btn btn-link">Limpiar</a>@endif</div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Factura</th><th>Fecha</th><th>Cliente</th><th>Cajero</th><th>Pago</th><th class="num">Total</th><th></th></tr></thead>
    <tbody>
    @forelse($ventas as $v)
        <tr><td class="fw-semibold"><a href="{{ route('ventas.show', $v) }}">{{ $v->factura?->numeroFactura ?? 'Venta #'.$v->idVenta }}</a></td><td>{{ $v->fechaHora->format('d/m/Y h:i a') }}</td>
            <td>{{ $v->cliente?->nombre ?? 'Consumidor final' }}</td><td>{{ $v->usuario->nombres }}</td><td>{{ ucfirst($v->metodoPago) }}</td><td class="num">{{ pesos($v->total) }}</td>
            <td class="text-end"><a href="{{ route('ventas.factura', $v) }}" target="_blank" class="btn btn-sm btn-light" title="Ver PDF"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
    @empty
        <tr><td colspan="7" class="text-muted">No hay ventas.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $ventas->links() }}</div>
@endsection
