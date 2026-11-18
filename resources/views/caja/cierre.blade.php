@extends('layouts.app')
@section('title', 'Cierre de caja')
@section('subtitle', 'Caja #'.$caja->idCaja.' abierta el '.$caja->fechaApertura->format('d/m/Y h:i a'))
@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card"><div class="card-header">Resumen calculado</div><ul class="list-group list-group-flush">
            <li class="list-group-item d-flex"><span>Monto inicial</span><span class="ms-auto">{{ pesos($resumen['montoInicial']) }}</span></li>
            <li class="list-group-item d-flex"><span>+ Ingresos en efectivo</span><span class="ms-auto text-success">{{ pesos($resumen['totalIngresos']) }}</span></li>
            <li class="list-group-item d-flex"><span>− Egresos</span><span class="ms-auto text-danger">{{ pesos($resumen['totalEgresos']) }}</span></li>
            <li class="list-group-item d-flex fw-bold fs-5"><span>Saldo final esperado</span><span class="ms-auto">{{ pesos($resumen['saldoEsperado']) }}</span></li>
        </ul></div>
        <div class="card mt-3"><div class="card-header">Ventas del turno ({{ $resumen['numeroVentas'] }})</div><ul class="list-group list-group-flush">
            @foreach(['efectivo','tarjeta','transferencia'] as $m)
                <li class="list-group-item d-flex"><span>{{ ucfirst($m) }}</span><span class="ms-auto">{{ pesos($resumen['ventasPorMetodo'][$m]->total ?? 0) }}</span></li>
            @endforeach
            <li class="list-group-item small text-muted">Tarjeta y transferencia no entran al efectivo de la caja.</li>
        </ul></div>
    </div>
    <div class="col-lg-6">
        <form method="POST" action="{{ route('caja.cerrar') }}" class="card" data-confirm="¿Confirmar el cierre de caja? Después no se podrán registrar más ventas en esta caja.">
            @csrf
            <div class="card-header">Verificación</div>
            <div class="card-body">
                <label class="form-label">Efectivo contado físicamente *</label>
                <div class="input-group input-group-lg mb-2"><span class="input-group-text">$</span><input type="number" step="0.01" min="0" name="montoContado" id="contado" value="{{ old('montoContado') }}" class="form-control" required autofocus></div>
                <div class="p-3 rounded mb-3 bg-light d-flex"><span>Diferencia</span><strong class="ms-auto" id="dif">—</strong></div>
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3" maxlength="300" placeholder="Explique cualquier diferencia o novedad">{{ old('observaciones') }}</textarea>
            </div>
            <div class="card-footer bg-transparent"><button class="btn btn-primary btn-lg w-100"><i class="bi bi-lock"></i> Confirmar cierre</button></div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
const esperado = {{ $resumen['saldoEsperado'] }};
document.getElementById('contado').addEventListener('input', e => {
    const el = document.getElementById('dif');
    if (e.target.value === '') { el.textContent = '—'; el.className = 'ms-auto'; return; }
    const d = Math.round((Number(e.target.value) - esperado) * 100) / 100;
    el.textContent = (d > 0 ? '+' : '') + MS.pesos(d) + (d === 0 ? ' (cuadrada)' : d > 0 ? ' (sobrante)' : ' (faltante)');
    el.className = 'ms-auto ' + (d === 0 ? 'text-success' : 'text-danger');
});
</script>
@endpush
