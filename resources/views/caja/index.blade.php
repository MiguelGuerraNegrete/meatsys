@extends('layouts.app')
@section('title', 'Caja')
@section('subtitle', $caja ? 'Abierta el '.$caja->fechaApertura->format('d/m/Y h:i a').' por '.$caja->usuario->nombre_completo : 'No hay caja abierta')
@section('actions')
    @if($caja && auth()->user()->esAdmin())<a href="{{ route('caja.cierre') }}" class="btn btn-primary"><i class="bi bi-lock"></i> Realizar cierre de caja</a>@endif
@endsection
@section('content')
@if(!$caja)
    <div class="card" style="max-width:460px"><form method="POST" action="{{ route('caja.abrir') }}" class="card-body">
        @csrf
        <h2 class="h5">Abrir caja del día</h2>
        <p class="text-muted small">Ingrese el dinero base (sencillo) con el que inicia la jornada.</p>
        <label class="form-label">Monto inicial *</label>
        <div class="input-group input-group-lg mb-3"><span class="input-group-text">$</span><input type="number" name="montoInicial" step="0.01" min="0" value="{{ old('montoInicial', 0) }}" class="form-control" required autofocus></div>
        <button class="btn btn-primary btn-lg w-100"><i class="bi bi-unlock"></i> Abrir caja</button>
    </form></div>
@else
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Monto inicial</div><div class="value">{{ pesos($resumen['montoInicial']) }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Ingresos en efectivo</div><div class="value text-success">{{ pesos($resumen['totalIngresos']) }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Egresos</div><div class="value text-danger">{{ pesos($resumen['totalEgresos']) }}</div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Efectivo esperado</div><div class="value">{{ pesos($resumen['saldoEsperado']) }}</div></div></div>
    </div>
    <div class="row g-3">
        <div class="col-lg-8"><div class="card"><div class="card-header">Movimientos del día</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Hora</th><th>Concepto</th><th>Tipo</th><th class="num">Monto</th></tr></thead>
            <tbody>
            @forelse($caja->movimientos->sortByDesc('fechaHora') as $m)
                <tr><td>{{ $m->fechaHora->format('h:i a') }}</td><td>{{ $m->concepto }}</td><td><span class="badge {{ $m->tipo === 'ingreso' ? 'text-bg-success' : 'text-bg-danger' }}">{{ ucfirst($m->tipo) }}</span></td><td class="num">{{ pesos($m->monto) }}</td></tr>
            @empty <tr><td colspan="4" class="text-muted">Sin movimientos todavía.</td></tr> @endforelse
            </tbody></table></div></div></div>
        <div class="col-lg-4">
            <div class="card mb-3"><div class="card-header">Ventas por método de pago</div><ul class="list-group list-group-flush">
                @foreach(['efectivo','tarjeta','transferencia'] as $m)
                    <li class="list-group-item d-flex"><span>{{ ucfirst($m) }} <span class="text-muted small">({{ $resumen['ventasPorMetodo'][$m]->cantidad ?? 0 }})</span></span><span class="ms-auto">{{ pesos($resumen['ventasPorMetodo'][$m]->total ?? 0) }}</span></li>
                @endforeach
                <li class="list-group-item d-flex fw-semibold"><span>Total ventas</span><span class="ms-auto">{{ pesos($resumen['totalVentas']) }}</span></li>
            </ul></div>
            <form method="POST" action="{{ route('caja.movimiento') }}" class="card">@csrf
                <div class="card-header">Registrar otro movimiento</div>
                <div class="card-body">
                    <select name="tipo" class="form-select mb-2"><option value="egreso">Egreso (gasto, retiro)</option><option value="ingreso">Ingreso (otro)</option></select>
                    <input name="concepto" class="form-control mb-2" placeholder="Concepto (p. ej. compra de bolsas)" maxlength="150" required>
                    <div class="input-group mb-2"><span class="input-group-text">$</span><input type="number" name="monto" step="0.01" min="0.01" class="form-control" required></div>
                    <button class="btn btn-outline-primary w-100">Registrar</button>
                </div>
            </form>
        </div>
    </div>
@endif

@if(auth()->user()->esAdmin() && $historial->count())
<div class="card mt-3"><div class="card-header">Cierres anteriores</div><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>#</th><th>Apertura</th><th>Cierre</th><th class="num">Esperado</th><th class="num">Contado</th><th class="num">Diferencia</th><th></th></tr></thead>
    <tbody>@foreach($historial as $h)
        @php($dif = $h->montoContado - $h->saldoFinal)
        <tr><td>{{ $h->idCaja }}</td><td>{{ $h->fechaApertura->format('d/m/Y h:i a') }}</td><td>{{ $h->fechaCierre->format('d/m/Y h:i a') }}</td><td class="num">{{ pesos($h->saldoFinal) }}</td><td class="num">{{ pesos($h->montoContado) }}</td>
            <td class="num {{ abs($dif) < 0.01 ? 'text-success' : 'text-danger' }}">{{ pesos($dif) }}</td>
            <td class="text-end"><a href="{{ route('caja.show', $h) }}" class="btn btn-sm btn-light">Ver</a> <a href="{{ route('caja.pdf', $h) }}" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
    @endforeach</tbody>
</table></div></div>
@endif
@endsection
