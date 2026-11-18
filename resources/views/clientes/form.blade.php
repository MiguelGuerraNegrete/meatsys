@extends('layouts.app')
@section('title', $cliente->exists ? 'Modificar cliente' : 'Registrar cliente')
@section('content')
<form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}" class="card" style="max-width:760px">
    @csrf @if($cliente->exists) @method('PUT') @endif
    @if(request('volver'))<input type="hidden" name="volver" value="{{ request('volver') }}">@endif
    <div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">Documento *</label><input name="documento" value="{{ old('documento', $cliente->documento) }}" class="form-control" maxlength="15" required></div>
        <div class="col-md-8"><label class="form-label">Nombre completo *</label><input name="nombre" value="{{ old('nombre', $cliente->nombre) }}" class="form-control" maxlength="120" required></div>
        <div class="col-md-4"><label class="form-label">Teléfono</label><input name="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="form-control" maxlength="15"></div>
        <div class="col-md-8"><label class="form-label">Correo</label><input type="email" name="email" value="{{ old('email', $cliente->email) }}" class="form-control" maxlength="80"><div class="form-text">Para enviarle sus facturas.</div></div>
        <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" value="{{ old('direccion', $cliente->direccion) }}" class="form-control" maxlength="150"></div>
        @unless($cliente->exists)
        <div class="col-12"><div class="form-check p-3 ps-5 border rounded bg-light">
            <input class="form-check-input" type="checkbox" name="autorizacion" value="1" id="aut" required @checked(old('autorizacion'))>
            <label class="form-check-label small" for="aut">El cliente autoriza el tratamiento de sus datos personales para la gestión de sus compras, pedidos y facturas, conforme a la Ley 1581 de 2012.</label>
        </div></div>
        @endunless
    </div>
    <div class="card-footer bg-transparent d-flex gap-2"><button class="btn btn-primary">Guardar</button><a href="{{ request('volver', route('clientes.index')) }}" class="btn btn-light">Cancelar</a></div>
</form>
@endsection
