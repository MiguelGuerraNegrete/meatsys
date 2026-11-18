@extends('layouts.app')
@section('title', $usuario->exists ? 'Modificar usuario' : 'Crear usuario')
@section('content')
<form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="card" style="max-width:820px">
    @csrf @if($usuario->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Nombres *</label><input name="nombres" value="{{ old('nombres', $usuario->nombres) }}" class="form-control" maxlength="60" required></div>
        <div class="col-md-6"><label class="form-label">Apellidos *</label><input name="apellidos" value="{{ old('apellidos', $usuario->apellidos) }}" class="form-control" maxlength="60" required></div>
        <div class="col-md-4"><label class="form-label">Documento *</label><input name="documento" value="{{ old('documento', $usuario->documento) }}" class="form-control" maxlength="15" required></div>
        <div class="col-md-4"><label class="form-label">Teléfono</label><input name="telefono" value="{{ old('telefono', $usuario->telefono) }}" class="form-control" maxlength="15"></div>
        <div class="col-md-4"><label class="form-label">Rol *</label>
            <select name="Rol_idRol" class="form-select" required>
                @foreach($roles as $r)<option value="{{ $r->idRol }}" @selected(old('Rol_idRol', $usuario->Rol_idRol) == $r->idRol)>{{ $r->etiqueta() }}</option>@endforeach
            </select></div>
        <div class="col-md-6"><label class="form-label">Correo electrónico *</label><input type="email" name="email" value="{{ old('email', $usuario->email) }}" class="form-control" maxlength="80" required><div class="form-text">Se usa para restablecer la contraseña.</div></div>
        <div class="col-md-6"><label class="form-label">Nombre de usuario *</label><input name="usuario" value="{{ old('usuario', $usuario->usuario) }}" class="form-control" maxlength="30" required></div>
        <div class="col-md-6"><label class="form-label">Contraseña {{ $usuario->exists ? '' : '*' }}</label><input type="password" name="contrasena" class="form-control" autocomplete="new-password" {{ $usuario->exists ? '' : 'required' }}>
            <div class="form-text">{{ $usuario->exists ? 'Déjela vacía para no cambiarla. ' : '' }}Mínimo 8 caracteres con letras y números.</div></div>
        <div class="col-md-6"><label class="form-label">Confirmar contraseña</label><input type="password" name="contrasena_confirmation" class="form-control" autocomplete="new-password"></div>
    </div>
    <div class="card-footer bg-transparent d-flex gap-2"><button class="btn btn-primary">Guardar</button><a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a></div>
</form>
@endsection
