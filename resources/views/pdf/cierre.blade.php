<!doctype html>
<html><head><meta charset="utf-8">@include('pdf._estilos')</head>
<body>
    <h1>{{ config('meatsys.negocio.nombre') }} — Cierre de caja #{{ $caja->idCaja }}</h1>
    <div class="muted">Apertura: {{ $caja->fechaApertura->format('d/m/Y h:i a') }} por {{ $caja->usuario->nombre_completo }} · Cierre: {{ $caja->fechaCierre?->format('d/m/Y h:i a') ?? 'abierta' }}</div>
    <h2>Resumen</h2>
    <table style="width:55%">
        <tr><td>Monto inicial</td><td class="num">{{ pesos($caja->montoInicial) }}</td></tr>
        <tr><td>+ Ingresos en efectivo</td><td class="num">{{ pesos($resumen['totalIngresos']) }}</td></tr>
        <tr><td>− Egresos</td><td class="num">{{ pesos($resumen['totalEgresos']) }}</td></tr>
        <tr><th>Saldo final esperado</th><th class="num">{{ pesos($caja->saldoFinal ?? $resumen['saldoEsperado']) }}</th></tr>
        <tr><td>Efectivo contado</td><td class="num">{{ $caja->montoContado !== null ? pesos($caja->montoContado) : '—' }}</td></tr>
        <tr><th>Diferencia</th><th class="num">{{ $caja->montoContado !== null ? pesos($caja->montoContado - $caja->saldoFinal) : '—' }}</th></tr>
    </table>
    <h2>Ventas por método de pago ({{ $resumen['numeroVentas'] }} ventas)</h2>
    <table style="width:55%">
        @foreach(['efectivo','tarjeta','transferencia'] as $m)<tr><td>{{ ucfirst($m) }}</td><td class="num">{{ pesos($resumen['ventasPorMetodo'][$m]->total ?? 0) }}</td></tr>@endforeach
        <tr><th>Total ventas</th><th class="num">{{ pesos($resumen['totalVentas']) }}</th></tr>
    </table>
    @if($caja->observaciones)<h2>Observaciones</h2><p>{{ $caja->observaciones }}</p>@endif
    <h2>Movimientos</h2>
    <table>
        <thead><tr><th>Fecha y hora</th><th>Concepto</th><th>Tipo</th><th class="num">Monto</th></tr></thead>
        <tbody>@foreach($caja->movimientos as $m)<tr><td>{{ $m->fechaHora->format('d/m/Y h:i a') }}</td><td>{{ $m->concepto }}</td><td>{{ ucfirst($m->tipo) }}</td><td class="num">{{ pesos($m->monto) }}</td></tr>@endforeach</tbody>
    </table>
    <p style="margin-top:40px">_____________________________<br>Firma del administrador</p>
</body></html>
