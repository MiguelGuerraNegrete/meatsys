@extends('layouts.app')
@section('title', 'Pedido #'.$pedido->idPedido)
@section('subtitle', $pedido->cliente->nombre.' · registrado '.$pedido->fechaPedido->format('d/m/Y h:i a').' por '.$pedido->usuario->nombres)
@section('actions')
    @if(in_array($pedido->estado, ['pendiente','listo']))
        @if($pedido->estado === 'pendiente')
            <form method="POST" action="{{ route('pedidos.estado', $pedido) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="listo"><button class="btn btn-outline-primary"><i class="bi bi-box2-heart"></i> Marcar como listo</button></form>
        @endif
        <a href="{{ route('ventas.create', ['pedido' => $pedido->idPedido]) }}" class="btn btn-primary"><i class="bi bi-cart-check"></i> Entregar y facturar</a>
        <form method="POST" action="{{ route('pedidos.estado', $pedido) }}" data-confirm="¿Marcar el pedido como entregado sin registrar la venta?">@csrf @method('PATCH')<input type="hidden" name="estado" value="entregado"><button class="btn btn-light">Solo marcar entregado</button></form>
        <form method="POST" action="{{ route('pedidos.estado', $pedido) }}" data-confirm="¿Cancelar este pedido?">@csrf @method('PATCH')<input type="hidden" name="estado" value="cancelado"><button class="btn btn-outline-danger">Cancelar pedido</button></form>
    @endif
@endsection
@section('content')
<div class="row g-3">
    <div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Producto</th><th class="num">Cantidad</th><th class="num">Precio actual</th><th class="num">Estimado</th></tr></thead>
        <tbody>@foreach($pedido->detalles as $d)
            <tr><td><div class="con-foto"><img src="{{ $d->producto->fotoUrl() }}" alt="" class="thumb"><span class="fw-semibold">{{ $d->producto->nombre }}</span></div></td><td class="num">{{ cant($d->cantidad, $d->producto->unidadVenta) }}</td><td class="num">{{ pesos($d->producto->precioVenta) }}</td><td class="num">{{ pesos($d->cantidad * $d->producto->precioVenta) }}</td></tr>
        @endforeach</tbody>
        <tfoot><tr><th colspan="3" class="text-end">Valor estimado</th><th class="num fs-5">{{ pesos($pedido->valorEstimado()) }}</th></tr></tfoot>
    </table></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body">
        <div class="mb-2">Estado: @include('partials.estado-pedido', ['estado' => $pedido->estado])</div>
        <div class="mb-2"><span class="text-muted">Entrega:</span> <strong>{{ $pedido->fechaEntrega->format('d/m/Y h:i a') }}</strong></div>
        <div class="mb-2"><span class="text-muted">Cliente:</span> <a href="{{ route('clientes.show', $pedido->cliente) }}">{{ $pedido->cliente->nombre }}</a> {{ $pedido->cliente->telefono ? '· '.$pedido->cliente->telefono : '' }}</div>
        @if($pedido->observaciones)<div class="p-2 bg-light rounded small"><i class="bi bi-chat-left-text"></i> {{ $pedido->observaciones }}</div>@endif
    </div></div></div>
</div>
@endsection
