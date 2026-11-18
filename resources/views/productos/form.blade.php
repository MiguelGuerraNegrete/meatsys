@extends('layouts.app')
@section('title', $producto->exists ? 'Modificar producto' : 'Crear producto')
@section('content')
<form method="POST" action="{{ $producto->exists ? route('productos.update', $producto) : route('productos.store') }}" enctype="multipart/form-data" class="card" style="max-width:1040px">
    @csrf @if($producto->exists) @method('PUT') @endif
    <div class="card-body row g-4">
        <div class="col-md-4">
            <label class="form-label">Foto del producto</label>
            <img id="preview" src="{{ $producto->exists ? $producto->fotoUrl() : asset('img/sin-foto.svg') }}" alt="Vista previa" class="foto-preview mb-2">
            <input type="file" name="imagen" id="imagen" accept="image/jpeg,image/png,image/webp" class="form-control">
            <div class="form-text">JPG, PNG o WEBP de hasta 5 MB. Se recorta automáticamente a 4:3.@if($producto->exists && !$producto->tieneFotoPropia()) Sin foto propia: se muestra la de la categoría.@endif</div>
            @if($producto->exists && $producto->tieneFotoPropia())
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="quitar_imagen" value="1" id="quitar"><label class="form-check-label small" for="quitar">Quitar la foto actual</label></div>
            @endif
        </div>
        <div class="col-md-8 row g-3 align-content-start">
            <div class="col-md-4"><label class="form-label">Código *</label><input name="codigo" value="{{ old('codigo', $producto->codigo) }}" class="form-control" maxlength="20" required><div class="form-text">Código interno o de barras.</div></div>
            <div class="col-md-8"><label class="form-label">Nombre *</label><input name="nombre" value="{{ old('nombre', $producto->nombre) }}" class="form-control" maxlength="100" required></div>
            <div class="col-md-6"><label class="form-label">Categoría *</label>
                <select name="Categoria_idCategoria" class="form-select" required>
                    <option value="">Seleccione…</option>
                    @foreach($categorias as $c)<option value="{{ $c->idCategoria }}" @selected(old('Categoria_idCategoria', $producto->Categoria_idCategoria) == $c->idCategoria)>{{ $c->nombre }}</option>@endforeach
                </select>
                @if($categorias->isEmpty())<div class="form-text text-danger">Primero <a href="{{ route('categorias.index') }}">cree una categoría</a>.</div>@endif
            </div>
            <div class="col-md-6"><label class="form-label">Tipo de corte</label><input name="tipoCorte" value="{{ old('tipoCorte', $producto->tipoCorte) }}" class="form-control" maxlength="60" placeholder="Lomo, costilla, pechuga, molida…"></div>
            <div class="col-md-4"><label class="form-label">Unidad de venta *</label>
                <select name="unidadVenta" class="form-select" required>
                    <option value="kg" @selected(old('unidadVenta', $producto->unidadVenta) === 'kg')>Por kilogramo (kg)</option>
                    <option value="unidad" @selected(old('unidadVenta', $producto->unidadVenta) === 'unidad')>Por unidad</option>
                </select></div>
            <div class="col-md-4"><label class="form-label">Precio de venta *</label><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" min="0.01" name="precioVenta" value="{{ old('precioVenta', $producto->precioVenta) }}" class="form-control" required></div><div class="form-text">Por kilogramo o por unidad.</div></div>
            <div class="col-md-4"><label class="form-label">Stock mínimo *</label><input type="number" step="0.001" min="0" name="stockMinimo" value="{{ old('stockMinimo', $producto->stockMinimo ?? 0) }}" class="form-control" required></div>
            @if($producto->exists)
                <div class="col-12"><div class="alert alert-light border mb-0 small"><i class="bi bi-info-circle"></i> Stock actual: <strong>{{ cant($producto->stockActual, $producto->unidadVenta) }}</strong>. El stock aumenta con el ingreso de mercancía y disminuye con las ventas.</div></div>
            @endif
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex gap-2"><button class="btn btn-primary">Guardar</button><a href="{{ route('productos.index') }}" class="btn btn-light">Cancelar</a></div>
</form>
@endsection
@push('scripts')
<script>
const original = document.getElementById('preview').src;
document.getElementById('imagen').addEventListener('change', e => {
    const f = e.target.files[0];
    document.getElementById('preview').src = f ? URL.createObjectURL(f) : original;
    const q = document.getElementById('quitar'); if (q && f) q.checked = false;
});
document.getElementById('quitar')?.addEventListener('change', e => {
    document.getElementById('preview').style.opacity = e.target.checked ? .35 : 1;
});
</script>
@endpush
