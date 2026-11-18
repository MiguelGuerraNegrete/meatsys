@extends('layouts.app')
@section('title', 'Categorías')
@section('subtitle', 'Res, cerdo, pollo, embutidos…')
@section('actions')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCat" data-modo="crear"><i class="bi bi-plus-lg"></i> Nueva categoría</button>@endsection
@section('content')
<div class="row g-3">
    @forelse($categorias as $c)
        <div class="col-sm-6 col-lg-4 col-xxl-3">
            <div class="card h-100 overflow-hidden">
                <a href="{{ route('productos.index', ['categoria' => $c->idCategoria]) }}" class="cat-tile rounded-0">
                    <img src="{{ $c->fotoUrl() }}" alt="{{ $c->nombre }}" loading="lazy">
                    <div class="cat-info"><div class="cat-nombre">{{ $c->nombre }}</div><div class="cat-sub">{{ $c->productos_count }} producto(s) activo(s)</div></div>
                </a>
                <div class="card-body d-flex align-items-start gap-2">
                    <div class="small text-muted flex-grow-1">{{ $c->descripcion ?: 'Sin descripción' }}</div>
                    <button class="btn btn-sm btn-light" title="Modificar" data-bs-toggle="modal" data-bs-target="#modalCat" data-modo="editar"
                        data-accion="{{ route('categorias.update', $c) }}" data-nombre="{{ $c->nombre }}" data-descripcion="{{ $c->descripcion }}" data-foto="{{ $c->fotoUrl() }}"><i class="bi bi-pencil"></i></button>
                    @if(!$c->productos()->exists())
                        <form method="POST" action="{{ route('categorias.destroy', $c) }}" data-confirm="¿Eliminar la categoría {{ $c->nombre }}?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Eliminar"><i class="bi bi-trash"></i></button></form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-center py-5 text-muted"><i class="bi bi-tags fs-1"></i><p class="mt-2 mb-0">Aún no hay categorías.</p></div></div></div>
    @endforelse
</div>

<div class="modal fade" id="modalCat" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('categorias.store') }}" enctype="multipart/form-data" class="modal-content" id="formCat">
            @csrf <input type="hidden" name="_method" value="POST" id="metodoCat">
            <div class="modal-header"><h5 class="modal-title" id="tituloCat">Nueva categoría</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <img id="previewCat" src="{{ asset('img/sin-foto.svg') }}" alt="" class="foto-preview mb-2" style="aspect-ratio:16/10">
                <input type="file" name="imagen" id="imagenCat" accept="image/jpeg,image/png,image/webp" class="form-control mb-3">
                <div class="mb-2"><label class="form-label">Nombre *</label><input name="nombre" id="nombreCat" value="{{ old('nombre') }}" class="form-control" maxlength="50" required></div>
                <div><label class="form-label">Descripción</label><textarea name="descripcion" id="descCat" class="form-control" rows="2" maxlength="200">{{ old('descripcion') }}</textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar</button></div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
const sinFoto = @json(asset('img/sin-foto.svg'));
document.getElementById('modalCat').addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget, editar = b?.dataset.modo === 'editar';
    document.getElementById('tituloCat').textContent = editar ? 'Modificar categoría' : 'Nueva categoría';
    document.getElementById('formCat').action = editar ? b.dataset.accion : @json(route('categorias.store'));
    document.getElementById('metodoCat').value = editar ? 'PUT' : 'POST';
    document.getElementById('nombreCat').value = editar ? b.dataset.nombre : '';
    document.getElementById('descCat').value = editar ? (b.dataset.descripcion || '') : '';
    document.getElementById('previewCat').src = editar ? b.dataset.foto : sinFoto;
    document.getElementById('imagenCat').value = '';
});
document.getElementById('imagenCat').addEventListener('change', e => {
    if (e.target.files[0]) document.getElementById('previewCat').src = URL.createObjectURL(e.target.files[0]);
});
</script>
@endpush
