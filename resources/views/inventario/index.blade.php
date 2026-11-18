@extends('layouts.app')
@section('title', 'Niveles de inventario')
@section('subtitle', 'Stock actual comparado con el stock mínimo')
@section('content')
<form class="d-flex gap-2 mb-3 flex-wrap" method="GET">
    <input name="q" value="{{ request('q') }}" class="form-control" style="max-width:280px" placeholder="Buscar producto">
    <select name="categoria" class="form-select" style="max-width:200px"><option value="">Todas las categorías</option>@foreach($categorias as $c)<option value="{{ $c->idCategoria }}" @selected(request('categoria') == $c->idCategoria)>{{ $c->nombre }}</option>@endforeach</select>
    <div class="form-check align-self-center"><input class="form-check-input" type="checkbox" name="bajo" value="1" id="bajo" @checked(request('bajo'))><label class="form-check-label" for="bajo">Solo bajo el mínimo</label></div>
    <button class="btn btn-outline-secondary">Filtrar</button>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Producto</th><th>Categoría</th><th class="num">Stock actual</th><th class="num">Stock mínimo</th><th style="width:200px">Nivel</th><th></th></tr></thead>
    <tbody>
    @forelse($productos as $p)
        @php($pct = $p->stockMinimo > 0 ? min(100, $p->stockActual / ($p->stockMinimo * 2) * 100) : 100)
        <tr class="{{ $p->bajoMinimo() ? 'row-alert' : '' }}">
            <td><div class="con-foto"><img src="{{ $p->fotoUrl() }}" alt="" class="thumb"><div><div class="fw-semibold">{{ $p->nombre }}</div><div class="small text-muted">{{ $p->codigo }}</div></div></div></td><td>{{ $p->categoria->nombre }}</td>
            <td class="num fw-semibold">{{ cant($p->stockActual, $p->unidadVenta) }}</td><td class="num">{{ cant($p->stockMinimo, $p->unidadVenta) }}</td>
            <td>@if($p->bajoMinimo())<span class="badge text-bg-warning"><i class="bi bi-exclamation-triangle"></i> Reabastecer</span>@else
                <div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $pct }}%"></div></div>@endif</td>
            <td class="text-end"><a href="{{ route('inventario.lotes', $p) }}" class="btn btn-sm btn-light">Lotes</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-muted">Sin productos.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
