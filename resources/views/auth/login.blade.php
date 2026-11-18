@extends('layouts.guest')
@section('title', 'Iniciar sesión')
@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="usuario">Usuario</label>
        <input id="usuario" name="usuario" value="{{ old('usuario') }}" class="form-control form-control-lg" autocomplete="username" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label" for="contrasena">Contraseña</label>
        <input id="contrasena" type="password" name="contrasena" class="form-control form-control-lg" autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary btn-lg w-100">Ingresar</button>
    <div class="text-center mt-3"><a href="{{ route('password.request') }}" class="small">¿Olvidó su contraseña?</a></div>
</form>
@endsection
