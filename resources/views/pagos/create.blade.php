@extends('layouts.app')
@section('title', 'Registrar pago')
@section('subtitle', 'Compra #'.$compra->idCompra.' · '.$compra->proveedor->razonSocial)
@section('content')
<div class="row g-3">
    <div class="col-lg-5">
        <form method="POST" action="{{ route('pagos.store', $compra) }}" class="card" data-confirm="¿Confirmar el pago al proveedor?">
            @csrf
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3 p-3 rounded bg-primary-soft"><span>Saldo pendiente</span><strong class="fs-5">{{ pesos($compra->saldoPendiente) }}</strong></div>
                <div class="mb-3"><label class="form-label">Monto *</label><div class="input-group"><span class="input-group-text">$</span>
                    <input type="number" name="monto" step="0.01" min="0.01" max="{{ $compra->saldoPendiente }}" value="{{ old('monto', $compra->saldoPendiente) }}" class="form-control form-control-lg" required></div></div>
                <div class="mb-3"><label class="form-label">Fecha *</label><input type="datetime-local" name="fechaPago" value="{{ old('fechaPago', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required></div>
                <div class="mb-1"><label class="form-label">Método de pago *</label>
                    <select name="metodoPago" class="form-select" required>
                        <option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="otro">Otro</option>
                    </select>
                    <div class="form-text">Los pagos en efectivo se registran como egreso en la caja abierta.</div></div>
            </div>
            <div class="card-footer bg-transparent d-flex gap-2"><button class="btn btn-primary">Confirmar pago</button><a href="{{ url()->previous() }}" class="btn btn-light">Cancelar</a></div>
        </form>
    </div>
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-header">Detalle de la compra</div><table class="table mb-0">
            @foreach($compra->detalles as $d)<tr><td>{{ $d->producto->nombre }}</td><td class="num">{{ cant($d->cantidad, $d->producto->unidadVenta) }}</td><td class="num">{{ pesos($d->subtotal) }}</td></tr>@endforeach
            <tr><th colspan="2">Total</th><th class="num">{{ pesos($compra->total) }}</th></tr>
        </table></div>
        @if($compra->pagos->count())
        <div class="card"><div class="card-header">Pagos anteriores</div><table class="table mb-0">
            @foreach($compra->pagos as $p)<tr><td>{{ $p->fechaPago->format('d/m/Y') }}</td><td>{{ ucfirst($p->metodoPago) }}</td><td class="num">{{ pesos($p->monto) }}</td></tr>@endforeach
        </table></div>
        @endif
    </div>
</div>
@endsection
