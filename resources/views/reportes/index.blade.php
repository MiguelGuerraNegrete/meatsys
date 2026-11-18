@extends('layouts.app')
@section('title', 'Reportes')
@section('subtitle', 'Información para decidir qué comprar, cuánto y cuándo')
@section('content')
@php($iconos = ['mas-vendidos' => 'bi-trophy', 'ganancias' => 'bi-graph-up-arrow', 'cierres' => 'bi-cash-stack', 'inventario' => 'bi-boxes'])
@php($desc = ['mas-vendidos' => 'Ranking de productos por ventas, cantidad y ganancia.', 'ganancias' => 'Ventas, costo de lo vendido y ganancia por día o mes.', 'cierres' => 'Historial de cierres de caja con sus diferencias.', 'inventario' => 'Stock actual, valorizado y productos bajo el mínimo.'])
<div class="row g-3">
@foreach($tipos as $k => $t)
    <div class="col-md-6 col-xl-3"><a href="{{ route('reportes.show', $k) }}" class="card h-100 text-decoration-none text-reset stat">
        <div class="icon mb-3"><i class="bi {{ $iconos[$k] }}"></i></div><div class="fw-bold fs-5">{{ $t }}</div><div class="text-muted small">{{ $desc[$k] }}</div>
    </a></div>
@endforeach
</div>
@endsection
