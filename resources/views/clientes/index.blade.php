@extends('layouts.app')
@section('title', 'Clientes frecuentes')
@section('actions')<a href="{{ route('clientes.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Registrar cliente</a>@endsection
@section('content')
@include('partials.buscar', ['placeholder' => 'Nombre, documento o teléfono'])
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Documento</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th class="num">Compras</th><th></th></tr></thead>
    <tbody>
    @forelse($clientes as $c)
        <tr><td>{{ $c->documento }}</td><td class="fw-semibold"><a href="{{ route('clientes.show', $c) }}" class="text-reset">{{ $c->nombre }}</a></td><td>{{ $c->telefono }}</td><td>{{ $c->email }}</td><td class="num">{{ $c->ventas_count }}</td>
            <td class="text-end text-nowrap"><a href="{{ route('clientes.show', $c) }}" class="btn btn-sm btn-light">Historial</a> <a href="{{ route('pedidos.create', ['cliente' => $c->idCliente]) }}" class="btn btn-sm btn-outline-primary">Nuevo pedido</a></td></tr>
    @empty
        <tr><td colspan="6" class="text-muted">No se encontraron clientes.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $clientes->links() }}</div>
@endsection
