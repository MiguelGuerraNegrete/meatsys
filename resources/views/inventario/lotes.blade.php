@extends('layouts.app')
@section('title', 'Lotes de '.$producto->nombre)
@section('subtitle', 'Stock actual '.cant($producto->stockActual, $producto->unidadVenta))
@section('actions')<a href="{{ route('inventario.index') }}" class="btn btn-light">Volver</a>@endsection
@section('content')
<div class="card mb-3"><div class="card-body con-foto"><img src="{{ $producto->fotoUrl() }}" alt="" class="thumb thumb-lg"><div><div class="fw-bold fs-5">{{ $producto->nombre }}</div><div class="text-muted small">{{ $producto->categoria->nombre }} · {{ pesos($producto->precioVenta) }}/{{ $producto->unidadCorta() }} · mínimo {{ cant($producto->stockMinimo, $producto->unidadVenta) }}</div></div></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Lote</th><th>Proveedor</th><th>Ingreso</th><th>Vence</th><th class="num">Inicial</th><th class="num">Disponible</th><th class="num">Costo unit.</th></tr></thead>
    <tbody>@forelse($lotes as $l)
        @php($d = $l->diasRestantes())
        <tr class="{{ $l->cantidadDisponible > 0 ? ($d < 0 ? 'row-danger' : ($d <= config('meatsys.dias_alerta_vencimiento') ? 'row-alert' : '')) : 'text-muted' }}">
            <td>#{{ $l->idLote }}</td><td>{{ $l->detalleCompra->compra->proveedor->razonSocial }}</td><td>{{ $l->fechaIngreso->format('d/m/Y') }}</td>
            <td>{{ $l->fechaVencimiento->format('d/m/Y') }} @if($l->cantidadDisponible > 0 && $d < 0)<span class="badge text-bg-danger">Vencido</span>@endif</td>
            <td class="num">{{ cant($l->cantidadInicial, $producto->unidadVenta) }}</td><td class="num fw-semibold">{{ cant($l->cantidadDisponible, $producto->unidadVenta) }}</td><td class="num">{{ pesos($l->detalleCompra->precioUnitario) }}</td></tr>
    @empty <tr><td colspan="7" class="text-muted">Sin lotes. Registre una compra para ingresar mercancía.</td></tr> @endforelse</tbody>
</table></div></div>
<div class="mt-3">{{ $lotes->links() }}</div>
@endsection
