@extends('layouts.guest')
@section('title', 'Nueva contraseña')
@section('content')
<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label">Correo electrónico</label>
        <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Código recibido</label>
        <input name="codigo" inputmode="numeric" maxlength="6" class="form-control form-control-lg text-center fw-bold" style="letter-spacing:.5em" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Nueva contraseña</label>
        <input type="password" name="contrasena" class="form-control" required autocomplete="new-password">
        <div class="form-text">Mínimo 8 caracteres, con letras y números.</div>
    </div>
    <div class="mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <input type="password" name="contrasena_confirmation" class="form-control" required autocomplete="new-password">
    </div>
    <button class="btn btn-primary btn-lg w-100">Guardar contraseña</button>
    <div class="text-center mt-3"><a href="{{ route('login') }}" class="small">Volver al inicio de sesión</a></div>
</form>
@endsection
