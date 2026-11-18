@extends('layouts.app')
@php($puedeEditar = auth()->user()->tieneRol('administrador','inventario'))
@php($catActual = $categorias->firstWhere('idCategoria', request('categoria')))
@section('title', $catActual ? $catActual->nombre : 'Productos')
@section('subtitle', $catActual?->descripcion ?? 'Catálogo de cortes y productos cárnicos')
@section('actions')@if($puedeEditar)<a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Crear producto</a>@endif @endsection
@section('content')
{{-- Filtro por categoría con foto --}}
<div class="chips mb-3">
    <a href="{{ request()->fullUrlWithQuery(['categoria' => null, 'page' => null]) }}" class="chip solo-texto text-decoration-none {{ !request('categoria') ? 'active' : '' }}">Todas</a>
    @foreach($categorias as $c)
        <a href="{{ request()->fullUrlWithQuery(['categoria' => $c->idCategoria, 'page' => null]) }}" class="chip text-decoration-none {{ request('categoria') == $c->idCategoria ? 'active' : '' }}">
            <img src="{{ $c->fotoUrl() }}" alt="">{{ $c->nombre }}
        </a>
    @endforeach
</div>

<form class="d-flex gap-2 mb-3 flex-wrap align-items-center" method="GET">
    @if(request('categoria'))<input type="hidden" name="categoria" value="{{ request('categoria') }}">@endif
    <input type="hidden" name="vista" value="{{ $vista }}">
    <div class="input-group" style="max-width:340px"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Nombre, código o corte"></div>
    @if($puedeEditar)<div class="form-check"><input class="form-check-input" type="checkbox" name="inactivos" value="1" id="inac" @checked(request('inactivos')) onchange="this.form.submit()"><label class="form-check-label" for="inac">Incluir inactivos</label></div>@endif
    <button class="btn btn-outline-secondary">Buscar</button>
    <div class="btn-group ms-auto" role="group" aria-label="Vista">
        <a href="{{ request()->fullUrlWithQuery(['vista' => 'tarjetas']) }}" class="btn btn-sm {{ $vista === 'tarjetas' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Tarjetas con foto"><i class="bi bi-grid-3x2-gap"></i></a>
        <a href="{{ request()->fullUrlWithQuery(['vista' => 'lista']) }}" class="btn btn-sm {{ $vista === 'lista' ? 'btn-primary' : 'btn-outline-secondary' }}" title="Lista"><i class="bi bi-list-ul"></i></a>
    </div>
</form>

@if($productos->isEmpty())
    <div class="card"><div class="card-body text-center py-5 text-muted"><i class="bi bi-search fs-1"></i><p class="mt-2 mb-0">No se encontraron productos.</p></div></div>
@elseif($vista === 'tarjetas')
<div class="row g-3">
    @foreach($productos as $p)
        @php($stockCls = !$p->activo ? '' : ((float) $p->stockActual <= 0 ? 'agotado' : ($p->bajoMinimo() ? 'bajo' : '')))
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card prod-card {{ $p->activo ? '' : 'inactivo' }}">
                <div class="foto">
                    <img src="{{ $p->fotoUrl() }}" alt="{{ $p->nombre }}" loading="lazy">
                    @if(!$p->activo)<span class="badge text-bg-secondary">Inactivo</span>
                    @elseif($stockCls === 'bajo')<span class="badge text-bg-warning"><i class="bi bi-exclamation-triangle"></i> Bajo mínimo</span>@endif
                    <span class="stock {{ $stockCls }}">{{ (float) $p->stockActual <= 0 ? 'Agotado' : cant($p->stockActual, $p->unidadVenta) }}</span>
                </div>
                <div class="card-body">
                    <div class="meta">{{ $p->categoria->nombre }}{{ $p->tipoCorte ? ' · '.$p->tipoCorte : '' }}</div>
                    <div class="nombre">{{ $p->nombre }}</div>
                    <div class="meta"><code>{{ $p->codigo }}</code></div>
                    <div class="d-flex align-items-end">
                        <div class="precio">{{ pesos($p->precioVenta) }} <small>/ {{ $p->unidadVenta === 'kg' ? 'kg' : 'und' }}</small></div>
                        @if($puedeEditar)
                        <div class="ms-auto d-flex gap-1">
                            <a href="{{ route('productos.edit', $p) }}" class="btn btn-sm btn-light" title="Modificar"><i class="bi bi-pencil"></i></a>
                            @if($p->activo)
                                <form method="POST" action="{{ route('productos.destroy', $p) }}" data-confirm="¿Eliminar «{{ $p->nombre }}»? Se marcará como inactivo y no podrá venderse.">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Eliminar"><i class="bi bi-trash"></i></button></form>
                            @else
                                <form method="POST" action="{{ route('productos.activar', $p) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" title="Activar"><i class="bi bi-arrow-counterclockwise"></i></button></form>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@else
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Producto</th><th>Categoría</th><th>Corte</th><th class="num">Precio</th><th class="num">Stock</th>@if($puedeEditar)<th></th>@endif</tr></thead>
    <tbody>
    @foreach($productos as $p)
        <tr class="{{ !$p->activo ? 'text-muted' : ($p->bajoMinimo() ? 'row-alert' : '') }}">
            <td><div class="con-foto"><img src="{{ $p->fotoUrl() }}" alt="" class="thumb"><div><div class="fw-semibold">{{ $p->nombre }} @if(!$p->activo)<span class="badge text-bg-secondary">Inactivo</span>@endif</div><div class="small text-muted"><code>{{ $p->codigo }}</code></div></div></div></td>
            <td>{{ $p->categoria->nombre }}</td><td>{{ $p->tipoCorte }}</td>
            <td class="num">{{ pesos($p->precioVenta) }}<span class="text-muted small">/{{ $p->unidadCorta() }}</span></td>
            <td class="num">{{ cant($p->stockActual, $p->unidadVenta) }} @if($p->activo && $p->bajoMinimo())<i class="bi bi-exclamation-triangle-fill text-warning" title="Bajo el mínimo ({{ cant($p->stockMinimo, $p->unidadVenta) }})"></i>@endif</td>
            @if($puedeEditar)
            <td class="text-end text-nowrap">
                <a href="{{ route('productos.edit', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                @if($p->activo)
                    <form method="POST" action="{{ route('productos.destroy', $p) }}" class="d-inline" data-confirm="¿Eliminar «{{ $p->nombre }}»? Se marcará como inactivo y no podrá venderse.">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                @else
                    <form method="POST" action="{{ route('productos.activar', $p) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Activar</button></form>
                @endif
            </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table></div></div>
@endif
<div class="mt-3">{{ $productos->links() }}</div>
@endsection
