@extends('layouts.app')
@section('title', $proveedor->exists ? 'Modificar proveedor' : 'Registrar proveedor')
@section('content')
<form method="POST" action="{{ $proveedor->exists ? route('proveedores.update', $proveedor) : route('proveedores.store') }}" class="card" style="max-width:760px">
    @csrf @if($proveedor->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">NIT *</label><input name="nit" value="{{ old('nit', $proveedor->nit) }}" class="form-control" maxlength="20" required></div>
        <div class="col-md-8"><label class="form-label">Razón social *</label><input name="razonSocial" value="{{ old('razonSocial', $proveedor->razonSocial) }}" class="form-control" maxlength="120" required></div>
        <div class="col-md-4"><label class="form-label">Teléfono</label><input name="telefono" value="{{ old('telefono', $proveedor->telefono) }}" class="form-control" maxlength="15"></div>
        <div class="col-md-8"><label class="form-label">Correo</label><input type="email" name="email" value="{{ old('email', $proveedor->email) }}" class="form-control" maxlength="80"></div>
        <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" value="{{ old('direccion', $proveedor->direccion) }}" class="form-control" maxlength="150"></div>
        @if($proveedor->exists)
            <div class="col-12"><input type="hidden" name="activo" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" value="1" id="act" @checked(old('activo', $proveedor->activo))><label class="form-check-label" for="act">Proveedor activo</label></div></div>
        @endif
    </div>
    <div class="card-footer bg-transparent d-flex gap-2"><button class="btn btn-primary">Guardar</button><a href="{{ route('proveedores.index') }}" class="btn btn-light">Cancelar</a></div>
</form>
@endsection
