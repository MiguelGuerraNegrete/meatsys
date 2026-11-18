@extends('layouts.app')
@section('title', 'Nuevo pedido')
@section('subtitle', 'Encargo especial de un cliente frecuente')
@section('content')
<form method="POST" action="{{ route('pedidos.store') }}" id="formPedido">
@csrf
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            @include('partials.selector-producto')
            <div class="table-responsive mt-3"><table class="table mb-0" id="tabla">
                <thead><tr><th>Producto</th><th style="width:170px">Cantidad</th><th class="num">Precio</th><th class="num">Estimado</th><th></th></tr></thead>
                <tbody><tr class="vacio"><td colspan="5" class="text-muted text-center py-4">Busque y agregue los productos del pedido.</td></tr></tbody>
            </table></div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <label class="form-label">Cliente *</label>
            <select name="cliente_id" id="cliente" class="form-select mb-1" required>
                @if($c = old('cliente_id') ? \App\Models\Cliente::find(old('cliente_id')) : $cliente)<option value="{{ $c->idCliente }}" selected>{{ $c->nombre }} ({{ $c->documento }})</option>@else<option value="">Seleccione…</option>@endif
            </select>
            <input id="buscarCliente" class="form-control form-control-sm mb-1" placeholder="Buscar cliente por nombre o documento">
            <div class="small mb-3"><a href="{{ route('clientes.create', ['volver' => route('pedidos.create')]) }}">+ Registrar cliente nuevo</a></div>
            <label class="form-label">Fecha y hora de entrega *</label>
            <input type="datetime-local" name="fechaEntrega" value="{{ old('fechaEntrega', now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i')) }}" min="{{ now()->format('Y-m-d\TH:i') }}" class="form-control mb-3" required>
            <label class="form-label">Observaciones</label>
            <textarea name="observaciones" class="form-control mb-3" rows="3" maxlength="300" placeholder="Tipo de corte, empaque, porciones…">{{ old('observaciones') }}</textarea>
            <div class="d-flex justify-content-between align-items-center p-3 rounded bg-primary-soft mb-3"><span>Valor estimado</span><strong class="fs-4" id="total">$0,00</strong></div>
            <button class="btn btn-primary btn-lg w-100"><i class="bi bi-check2-circle"></i> Confirmar pedido</button>
        </div></div>
    </div>
</div>
</form>
@endsection
@push('scripts')
@include('partials.carrito-js', ['validarStock' => false])
<script>
// Buscador de clientes
const sel = document.getElementById('cliente');
document.getElementById('buscarCliente').addEventListener('input', MS.debounce(async (e) => {
    const r = await fetch(`{{ route('clientes.buscar') }}?q=${encodeURIComponent(e.target.value)}`, { headers: { Accept: 'application/json' } });
    const lista = await r.json();
    const actual = sel.value;
    sel.innerHTML = '<option value="">Seleccione…</option>' + lista.map(c => `<option value="${c.id}">${c.nombre} (${c.documento})</option>`).join('');
    if (lista.length === 1) sel.value = lista[0].id; else sel.value = actual;
}));
</script>
@endpush
