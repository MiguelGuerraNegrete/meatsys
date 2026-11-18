@extends('layouts.app')
@section('title', 'Alertas de vencimiento')
@section('subtitle', 'Lotes con producto disponible que vencen en los próximos '.config('meatsys.dias_alerta_vencimiento').' días')
@section('actions')<form method="POST" action="{{ route('alertas.generar') }}">@csrf<button class="btn btn-outline-primary"><i class="bi bi-arrow-repeat"></i> Revisar lotes ahora</button></form>@endsection
@section('content')
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link {{ $estado === 'pendiente' ? 'active' : '' }}" href="{{ route('alertas.index') }}">Pendientes</a></li>
    <li class="nav-item"><a class="nav-link {{ $estado === 'atendida' ? 'active' : '' }}" href="{{ route('alertas.index', ['estado' => 'atendida']) }}">Atendidas</a></li>
</ul>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Producto</th><th>Lote</th><th>Vence</th><th>Días restantes</th><th class="num">Disponible</th>@if($estado === 'pendiente')<th style="min-width:330px">Acción</th>@else<th>Acción tomada</th>@endif</tr></thead>
    <tbody>
    @forelse($alertas as $a)
        @php($d = $estado === 'pendiente' ? $a->lote->diasRestantes() : $a->diasRestantes)
        <tr class="{{ $estado === 'pendiente' ? ($d < 0 ? 'row-danger' : ($d <= 1 ? 'row-alert' : '')) : '' }}">
            <td><div class="con-foto"><img src="{{ $a->lote->producto->fotoUrl() }}" alt="" class="thumb"><div><div class="fw-semibold">{{ $a->lote->producto->nombre }}</div><div class="small text-muted">{{ $a->lote->producto->categoria->nombre }}</div></div></div></td>
            <td>#{{ $a->lote->idLote }}</td><td>{{ $a->lote->fechaVencimiento->format('d/m/Y') }}</td>
            <td>@if($d < 0)<span class="badge text-bg-danger">Vencido hace {{ abs($d) }} día(s)</span>@elseif($d === 0)<span class="badge text-bg-danger">Vence hoy</span>@else<span class="badge text-bg-warning">{{ $d }} día(s)</span>@endif</td>
            <td class="num">{{ cant($a->lote->cantidadDisponible, $a->lote->producto->unidadVenta) }}</td>
            @if($estado === 'pendiente')
            <td><form method="POST" action="{{ route('alertas.atender', $a) }}" class="d-flex gap-1" data-confirm="¿Marcar la alerta como atendida? Si elige «Retiro», el saldo del lote se descontará del inventario.">
                @csrf @method('PATCH')
                <select name="accion" class="form-select form-select-sm" style="width:120px"><option value="promoción">Promoción</option><option value="retiro">Retiro</option><option value="otra">Otra</option></select>
                <input name="detalle" class="form-control form-control-sm" placeholder="Detalle (opcional)" maxlength="150">
                <button class="btn btn-sm btn-primary">Atender</button>
            </form></td>
            @else
            <td class="small">{{ $a->accionTomada }}</td>
            @endif
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted"><i class="bi bi-check-circle text-success"></i> {{ $estado === 'pendiente' ? 'No hay lotes próximos a vencer.' : 'Sin alertas atendidas.' }}</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $alertas->links() }}</div>
@endsection
