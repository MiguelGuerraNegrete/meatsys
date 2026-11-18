@extends('layouts.app')
@section('title', 'Nueva compra')
@section('subtitle', 'Registre la mercancía recibida. Cada producto crea un lote con su fecha de vencimiento.')
@section('content')
<form method="POST" action="{{ route('compras.store') }}" id="formCompra">
    @csrf
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-5"><label class="form-label">Proveedor *</label>
            <select name="proveedor_id" class="form-select" required><option value="">Seleccione…</option>
                @foreach($proveedores as $p)<option value="{{ $p->idProveedor }}" @selected(old('proveedor_id') == $p->idProveedor)>{{ $p->razonSocial }} ({{ $p->nit }})</option>@endforeach
            </select></div>
        <div class="col-md-3"><label class="form-label">N.º factura del proveedor</label><input name="numero_factura" value="{{ old('numero_factura') }}" class="form-control" maxlength="30"></div>
        <div class="col-md-4"><label class="form-label">Fecha y hora de ingreso *</label><input type="datetime-local" name="fecha_compra" id="fechaCompra" value="{{ old('fecha_compra', now()->format('Y-m-d\TH:i')) }}" max="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required></div>
    </div></div>

    <div class="card">
        <div class="card-header d-flex align-items-center">Productos recibidos <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="agregar"><i class="bi bi-plus-lg"></i> Agregar producto</button></div>
        <div class="table-responsive"><table class="table mb-0" id="tabla">
            <thead><tr><th style="min-width:260px">Producto</th><th style="width:140px">Cantidad</th><th style="width:170px">Precio unitario</th><th style="width:170px">Vence</th><th class="num" style="width:150px">Subtotal</th><th style="width:50px"></th></tr></thead>
            <tbody></tbody>
            <tfoot><tr><th colspan="4" class="text-end">Total de la compra</th><th class="num fs-5" id="total">$0,00</th><th></th></tr></tfoot>
        </table></div>
    </div>
    <div class="d-flex gap-2 mt-3"><button class="btn btn-primary btn-lg"><i class="bi bi-check2-circle"></i> Confirmar compra</button><a href="{{ route('compras.index') }}" class="btn btn-light btn-lg">Cancelar</a></div>
</form>

<template id="fila">
    <tr>
        <td><select class="form-select form-select-sm prod" required><option value="">Seleccione…</option>
            @foreach($productos as $p)<option value="{{ $p->idProducto }}" data-unidad="{{ $p->unidadVenta }}">{{ $p->nombre }} · {{ $p->codigo }}</option>@endforeach
        </select></td>
        <td><div class="input-group input-group-sm"><input type="number" step="0.001" min="0.001" class="form-control cant" required><span class="input-group-text uni">kg</span></div></td>
        <td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" step="0.01" min="0.01" class="form-control precio" required></div></td>
        <td><input type="date" class="form-control form-control-sm venc" required></td>
        <td class="num sub">$0,00</td>
        <td><button type="button" class="btn btn-sm btn-light quitar" title="Quitar"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>
@endsection
@push('scripts')
<script>
const tbody = document.querySelector('#tabla tbody'), tpl = document.getElementById('fila');
const viejos = @json(old('items', []));
function renumerar() {
    tbody.querySelectorAll('tr').forEach((tr, i) => {
        tr.querySelector('.prod').name = `items[${i}][producto_id]`;
        tr.querySelector('.cant').name = `items[${i}][cantidad]`;
        tr.querySelector('.precio').name = `items[${i}][precio_unitario]`;
        tr.querySelector('.venc').name = `items[${i}][fecha_vencimiento]`;
    });
}
function minVenc() {
    const f = document.getElementById('fechaCompra').value.slice(0, 10);
    const d = new Date(f + 'T00:00'); d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
}
function recalcular() {
    let total = 0;
    tbody.querySelectorAll('tr').forEach(tr => {
        const s = MS.subtotal(tr.querySelector('.cant').value || 0, tr.querySelector('.precio').value || 0);
        tr.querySelector('.sub').textContent = MS.pesos(s); total += s;
        const opt = tr.querySelector('.prod').selectedOptions[0];
        const u = opt?.dataset.unidad || 'kg';
        tr.querySelector('.uni').textContent = u === 'kg' ? 'kg' : 'und';
        tr.querySelector('.cant').step = u === 'kg' ? '0.001' : '1';
        tr.querySelector('.cant').min = u === 'kg' ? '0.001' : '1';
        tr.querySelector('.venc').min = minVenc();
    });
    document.getElementById('total').textContent = MS.pesos(total);
}
function agregar(v = {}) {
    const tr = tpl.content.firstElementChild.cloneNode(true);
    tr.querySelector('.prod').value = v.producto_id || '';
    tr.querySelector('.cant').value = v.cantidad || '';
    tr.querySelector('.precio').value = v.precio_unitario || '';
    tr.querySelector('.venc').value = v.fecha_vencimiento || '';
    tr.querySelector('.quitar').onclick = () => { tr.remove(); renumerar(); recalcular(); };
    tbody.appendChild(tr); renumerar(); recalcular();
}
tbody.addEventListener('input', recalcular);
tbody.addEventListener('change', recalcular);
document.getElementById('fechaCompra').addEventListener('change', recalcular);
document.getElementById('agregar').onclick = () => agregar();
(viejos.length ? viejos : [{}]).forEach(agregar);
</script>
@endpush
