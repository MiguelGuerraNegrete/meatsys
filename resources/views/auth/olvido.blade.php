@extends('layouts.guest')
@section('title', 'Restablecer contraseña')
@section('content')
<p class="text-muted small">Ingrese el correo registrado. Le enviaremos un código de 6 dígitos para crear una nueva contraseña.</p>
<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="mb-3">
        <label class="form-label" for="email">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus>
    </div>
    <button class="btn btn-primary btn-lg w-100">Enviar código</button>
    <div class="text-center mt-3"><a href="{{ route('login') }}" class="small">Volver al inicio de sesión</a></div>
</form>
@endsection
