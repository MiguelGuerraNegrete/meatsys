@extends('layouts.app')
@section('title', 'Nueva venta')
@section('subtitle', $pedido ? 'Entrega del pedido #'.$pedido->idPedido.' de '.$pedido->cliente->nombre : 'Toque un producto, ingrese el peso o la cantidad y cobre.')
@section('content')
@if(!$caja)
    <div class="card overflow-hidden" style="max-width:640px">
        <img src="{{ asset('img/portada/panel.jpg') }}" alt="" style="height:180px;object-fit:cover;width:100%">
        <div class="card-body text-center py-4">
            <i class="bi bi-lock fs-2 text-muted"></i>
            <h2 class="h5 mt-2">La caja está cerrada</h2>
            <p class="text-muted">Abra la caja del día para poder registrar ventas.</p>
            <a href="{{ route('caja.index') }}" class="btn btn-primary">Ir a caja</a>
        </div>
    </div>
@else
<form method="POST" action="{{ route('ventas.store') }}" id="formVenta">
@csrf
@if($pedido)<input type="hidden" name="pedido_id" value="{{ $pedido->idPedido }}">@endif
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card"><div class="card-body">
            @include('partials.selector-producto')
        </div></div>
    </div>
    <div class="col-xl-5">
        <div class="card position-sticky" style="top:1rem">
            <div class="card-header d-flex align-items-center"><i class="bi bi-receipt me-2"></i> Venta actual</div>
            <div class="table-responsive" style="max-height:340px;overflow-y:auto"><table class="table table-sm mb-0" id="tabla">
                <tbody></tbody>
            </table></div>
            <div class="card-body border-top">
                <label class="form-label small mb-1">Cliente <span class="text-muted">(opcional)</span></label>
                <div class="d-flex gap-2 mb-3">
                    <select name="cliente_id" id="cliente" class="form-select form-select-sm">
                        @php($cli = old('cliente_id') ? \App\Models\Cliente::find(old('cliente_id')) : $pedido?->cliente)
                        <option value="">Consumidor final</option>
                        @if($cli)<option value="{{ $cli->idCliente }}" selected>{{ $cli->nombre }} ({{ $cli->documento }})</option>@endif
                    </select>
                    <input id="buscarCliente" class="form-control form-control-sm" placeholder="Buscar cliente…" style="max-width:170px">
                </div>

                <div class="btn-group w-100 mb-3" role="group" aria-label="Método de pago">
                    @foreach(['efectivo' => 'bi-cash', 'tarjeta' => 'bi-credit-card', 'transferencia' => 'bi-phone'] as $m => $ic)
                        <input type="radio" class="btn-check" name="metodo_pago" id="mp{{ $m }}" value="{{ $m }}" @checked(old('metodo_pago', 'efectivo') === $m)>
                        <label class="btn btn-outline-primary btn-sm" for="mp{{ $m }}"><i class="bi {{ $ic }}"></i> {{ ucfirst($m) }}</label>
                    @endforeach
                </div>

                <div class="d-flex justify-content-between text-muted small"><span>Subtotal</span><span id="subtotal">$0,00</span></div>
                @if(config('meatsys.impuesto_porcentaje') > 0)
                <div class="d-flex justify-content-between text-muted small"><span>Impuesto ({{ config('meatsys.impuesto_porcentaje') }}%)</span><span id="impuesto">$0,00</span></div>
                @endif
                <div class="d-flex justify-content-between align-items-end"><span class="fw-semibold">Total</span><span class="pos-total text-primary" id="totalFinal">$0,00</span></div>
                <span id="total" class="d-none"></span>

                <div id="efectivoBox" class="mt-2 d-flex gap-2 align-items-center">
                    <div class="input-group input-group-sm"><span class="input-group-text">Recibido $</span><input type="number" step="100" min="0" id="recibido" class="form-control"></div>
                    <div class="text-nowrap small">Cambio <strong id="cambio">$0,00</strong></div>
                </div>

                <button class="btn btn-primary btn-lg w-100 mt-3" id="cobrar" disabled><i class="bi bi-check2-circle"></i> Cobrar y facturar</button>
                <div class="small text-muted mt-2 text-center">El stock se descuenta de los lotes que vencen primero.</div>
            </div>
        </div>
    </div>
</div>
</form>
@endif
@endsection
@push('scripts')
@if($caja)
@php($precargaPedido = $pedido ? $pedido->detalles->map(fn($d) => ['p' => $d->producto, 'c' => $d->cantidad]) : collect())
@include('partials.carrito-js', ['validarStock' => true, 'compacto' => true, 'precargaPedido' => $precargaPedido])
<script>
const IVA = {{ (float) config('meatsys.impuesto_porcentaje') }};
let totalVenta = 0;
function cambio() {
    const r = Number(document.getElementById('recibido').value || 0);
    document.getElementById('cambio').textContent = MS.pesos(r >= totalVenta ? r - totalVenta : 0);
}
document.addEventListener('carrito', e => {
    const sub = e.detail.subtotal, imp = Math.round(sub * IVA) / 100;
    totalVenta = Math.round((sub + imp) * 100) / 100;
    document.getElementById('subtotal').textContent = MS.pesos(sub);
    const ei = document.getElementById('impuesto'); if (ei) ei.textContent = MS.pesos(imp);
    document.getElementById('totalFinal').textContent = MS.pesos(totalVenta);
    const ok = e.detail.lineas.length && e.detail.lineas.every(l => Number(l.cantidad) > 0 && Number(l.cantidad) <= l.stock);
    document.getElementById('cobrar').disabled = !ok;
    cambio();
});
document.getElementById('recibido').addEventListener('input', cambio);
document.querySelectorAll('[name=metodo_pago]').forEach(r => r.addEventListener('change', () => {
    document.getElementById('efectivoBox').style.setProperty('display', document.getElementById('mpefectivo').checked ? 'flex' : 'none', 'important');
}));
Carrito.pintar();
if (!document.getElementById('buscarProd').value) document.getElementById('buscarProd').focus();

const sel = document.getElementById('cliente');
document.getElementById('buscarCliente').addEventListener('input', MS.debounce(async (e) => {
    const r = await fetch(`{{ route('clientes.buscar') }}?q=${encodeURIComponent(e.target.value)}`, { headers: { Accept: 'application/json' } });
    const lista = await r.json();
    sel.innerHTML = '<option value="">Consumidor final</option>' + lista.map(c => `<option value="${c.id}">${c.nombre} (${c.documento})</option>`).join('');
    if (lista.length === 1) sel.value = lista[0].id;
}));
</script>
@endif
@endpush
