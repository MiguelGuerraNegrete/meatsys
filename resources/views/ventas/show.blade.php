@extends('layouts.app')
@section('title', 'Factura '.($venta->factura?->numeroFactura ?? '#'.$venta->idVenta))
@section('subtitle', $venta->fechaHora->format('d/m/Y h:i a').' · atendió '.$venta->usuario->nombre_completo)
@section('actions')
    <a href="{{ route('ventas.factura', $venta) }}" target="_blank" class="btn btn-primary" id="btnImprimir"><i class="bi bi-printer"></i> Imprimir</a>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEnviar"><i class="bi bi-envelope"></i> Enviar por correo</button>
    <a href="{{ route('ventas.create') }}" class="btn btn-light"><i class="bi bi-cart-plus"></i> Nueva venta</a>
@endsection
@section('content')
<div class="row g-3">
    <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Producto</th><th class="num">Peso / cant.</th><th class="num">Precio</th><th class="num">Subtotal</th></tr></thead>
        <tbody>@foreach($lineas as $l)
            <tr><td><div class="con-foto"><img src="{{ $l->producto->fotoUrl() }}" alt="" class="thumb"><span class="fw-semibold">{{ $l->producto->nombre }}</span></div></td><td class="num">{{ cant($l->cantidad, $l->producto->unidadVenta) }}</td><td class="num">{{ pesos($l->precioUnitario) }}/{{ $l->producto->unidadCorta() }}</td><td class="num">{{ pesos($l->subtotal) }}</td></tr>
        @endforeach</tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end">Subtotal</td><td class="num">{{ pesos($venta->subtotal) }}</td></tr>
            @if($venta->impuesto > 0)<tr><td colspan="3" class="text-end">Impuesto</td><td class="num">{{ pesos($venta->impuesto) }}</td></tr>@endif
            <tr><th colspan="3" class="text-end fs-5">Total</th><th class="num fs-5 text-primary">{{ pesos($venta->total) }}</th></tr>
        </tfoot>
    </table></div></div></div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <div class="mb-2"><span class="text-muted">Cliente:</span> @if($venta->cliente)<a href="{{ route('clientes.show', $venta->cliente) }}">{{ $venta->cliente->nombre }}</a>@else Consumidor final @endif</div>
            <div class="mb-2"><span class="text-muted">Método de pago:</span> {{ ucfirst($venta->metodoPago) }}</div>
            <div><span class="text-muted">Caja:</span> #{{ $venta->Caja_idCaja }}</div>
        </div></div>
        <div class="card"><div class="card-header">Lotes descontados</div><ul class="list-group list-group-flush small">
            @foreach($venta->detalles as $d)<li class="list-group-item d-flex"><span>{{ $d->producto->nombre }} · lote #{{ $d->Lote_idLote }} <span class="text-muted">(vence {{ $d->lote->fechaVencimiento->format('d/m/Y') }})</span></span><span class="ms-auto">{{ cant($d->cantidad, $d->producto->unidadVenta) }}</span></li>@endforeach
        </ul></div>
    </div>
</div>

<div class="modal fade" id="modalEnviar" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('ventas.enviar', $venta) }}" class="modal-content">
    @csrf
    <div class="modal-header"><h5 class="modal-title">Enviar factura por correo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label">Correo del cliente</label><input type="email" name="email" value="{{ $venta->cliente?->email }}" class="form-control" required></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Enviar</button></div>
</form></div></div>
@endsection
@if(session('imprimir'))
@push('scripts')
<script>window.open(@json(route('ventas.factura', $venta)), '_blank');</script>
@endpush
@endif
