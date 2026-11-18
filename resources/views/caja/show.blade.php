@extends('layouts.app')
@section('title', 'Caja #'.$caja->idCaja)
@section('subtitle', 'Del '.$caja->fechaApertura->format('d/m/Y h:i a').($caja->fechaCierre ? ' al '.$caja->fechaCierre->format('d/m/Y h:i a') : ''))
@section('actions')<a href="{{ route('caja.pdf', $caja) }}" target="_blank" class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Reporte PDF</a><a href="{{ route('caja.index') }}" class="btn btn-light">Volver</a>@endsection
@section('content')
@php($dif = $caja->montoContado - $caja->saldoFinal)
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Monto inicial</div><div class="value">{{ pesos($caja->montoInicial) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Saldo esperado</div><div class="value">{{ pesos($caja->saldoFinal) }}</div><div class="small text-muted">+{{ pesos($caja->totalIngresos) }} / −{{ pesos($caja->totalEgresos) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Efectivo contado</div><div class="value">{{ pesos($caja->montoContado) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card stat"><div class="label">Diferencia</div><div class="value {{ abs($dif) < 0.01 ? 'text-success' : 'text-danger' }}">{{ pesos($dif) }}</div></div></div>
</div>
@if($caja->observaciones)<div class="alert alert-light border"><i class="bi bi-chat-left-text"></i> {{ $caja->observaciones }}</div>@endif
<div class="card"><div class="card-header">Movimientos</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Hora</th><th>Concepto</th><th>Tipo</th><th class="num">Monto</th></tr></thead>
    <tbody>@foreach($caja->movimientos as $m)<tr><td>{{ $m->fechaHora->format('d/m h:i a') }}</td><td>{{ $m->concepto }}</td><td>{{ ucfirst($m->tipo) }}</td><td class="num">{{ pesos($m->monto) }}</td></tr>@endforeach</tbody>
</table></div></div>
@endsection
