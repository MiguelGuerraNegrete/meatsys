@extends('layouts.app')
@section('title', 'Proveedores')
@section('actions')<a href="{{ route('proveedores.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Registrar proveedor</a>@endsection
@section('content')
@include('partials.buscar', ['placeholder' => 'Razón social o NIT'])
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>NIT</th><th>Razón social</th><th>Teléfono</th><th>Correo</th><th class="num">Compras</th><th class="num">Saldo pendiente</th><th></th></tr></thead>
    <tbody>
    @forelse($proveedores as $p)
        <tr class="{{ $p->activo ? '' : 'text-muted' }}">
            <td>{{ $p->nit }}</td>
            <td class="fw-semibold"><a href="{{ route('proveedores.show', $p) }}" class="text-reset">{{ $p->razonSocial }}</a> @if(!$p->activo)<span class="badge text-bg-secondary">Inactivo</span>@endif</td>
            <td>{{ $p->telefono }}</td><td>{{ $p->email }}</td><td class="num">{{ $p->compras_count }}</td>
            <td class="num {{ $p->saldo > 0 ? 'text-danger fw-semibold' : '' }}">{{ pesos($p->saldo) }}</td>
            <td class="text-end text-nowrap">
                <a href="{{ route('proveedores.edit', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>
                <form method="POST" action="{{ route('proveedores.destroy', $p) }}" class="d-inline" data-confirm="¿Eliminar el proveedor {{ $p->razonSocial }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted">Sin proveedores registrados.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $proveedores->links() }}</div>
@endsection
