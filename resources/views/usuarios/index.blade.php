@extends('layouts.app')
@section('title', 'Usuarios')
@section('subtitle', 'Cuentas del sistema y su rol')
@section('actions')<a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> Crear usuario</a>@endsection
@section('content')
@include('partials.buscar', ['placeholder' => 'Nombre, usuario o documento'])
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Nombre</th><th>Usuario</th><th>Documento</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach($usuarios as $u)
        <tr class="{{ $u->activo ? '' : 'text-muted' }}">
            <td class="fw-semibold">{{ $u->nombre_completo }}</td><td>{{ $u->usuario }}</td><td>{{ $u->documento }}</td><td>{{ $u->email }}</td>
            <td><span class="badge badge-soft">{{ $u->rol->etiqueta() }}</span></td>
            <td>@if($u->activo)<span class="badge text-bg-success">Activo</span>@else<span class="badge text-bg-secondary">Inactivo</span>@endif</td>
            <td class="text-end text-nowrap">
                <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i> Modificar</a>
                @unless($u->is(auth()->user()))
                <form method="POST" action="{{ route('usuarios.toggle', $u) }}" class="d-inline" data-confirm="{{ $u->activo ? '¿Desactivar la cuenta de '.$u->nombre_completo.'? No podrá iniciar sesión.' : '¿Activar la cuenta de '.$u->nombre_completo.'?' }}">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm {{ $u->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button>
                </form>
                @endunless
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div></div>
<div class="mt-3">{{ $usuarios->links() }}</div>
@endsection
