@extends('layouts.app')
@section('title', 'Pagos a proveedores')
@section('subtitle', 'Compras con saldo pendiente')
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Factura</th><th class="num">Total</th><th class="num">Saldo pendiente</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse($compras as $c)
        <tr><td>{{ $c->idCompra }}</td><td>{{ $c->fechaCompra->format('d/m/Y') }} <span class="small text-muted">({{ $c->fechaCompra->locale('es')->diffForHumans() }})</span></td>
            <td class="fw-semibold">{{ $c->proveedor->razonSocial }}</td><td>{{ $c->numeroFactura }}</td><td class="num">{{ pesos($c->total) }}</td>
            <td class="num text-danger fw-semibold">{{ pesos($c->saldoPendiente) }}</td><td>@include('partials.estado-pago', ['estado' => $c->estadoPago])</td>
            <td class="text-end"><a href="{{ route('pagos.create', $c) }}" class="btn btn-sm btn-primary">Pagar</a></td></tr>
    @empty
        <tr><td colspan="8" class="text-muted"><i class="bi bi-check-circle text-success"></i> No hay compras con saldo pendiente.</td></tr>
    @endforelse
    </tbody>
    @if($compras->count())<tfoot><tr><th colspan="5" class="text-end">Total por pagar</th><th class="num">{{ pesos($compras->sum('saldoPendiente')) }}</th><th colspan="2"></th></tr></tfoot>@endif
</table></div></div>
@endsection
