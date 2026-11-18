{{-- Buscador + selección rápida con fotos (venta y pedido) --}}
<div class="position-relative mb-3">
    <div class="input-group input-group-lg">
        <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
        <input id="buscarProd" class="form-control" placeholder="Buscar o escanear: nombre, código, corte…" autocomplete="off">
    </div>
    <div class="list-group position-absolute w-100 shadow pos-results" id="resultados" style="z-index:20;max-height:360px;overflow:auto"></div>
</div>
<div class="chips mb-3" id="chipsCat">
    <button type="button" class="chip solo-texto active" data-cat="">Todos</button>
    @foreach(\App\Models\Categoria::orderBy('nombre')->get() as $c)
        <button type="button" class="chip" data-cat="{{ $c->idCategoria }}"><img src="{{ $c->fotoUrl() }}" alt="">{{ $c->nombre }}</button>
    @endforeach
</div>
<div class="pos-grid" id="grilla" aria-label="Productos"></div>
