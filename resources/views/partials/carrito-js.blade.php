{{-- Lógica de carrito compartida por "Nueva venta" y "Nuevo pedido" --}}
<script>
const Carrito = (() => {
    const validarStock = @json($validarStock);
    const compacto = @json($compacto ?? false);
    const tbody = document.querySelector('#tabla tbody');
    const input = document.getElementById('buscarProd');
    const res = document.getElementById('resultados');
    const grilla = document.getElementById('grilla');
    const URL_BUSCAR = @json(route('productos.buscar'));
    let lineas = [], encontrados = [], idx = -1, catActual = '';

    function esc(s) { return String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
    const uni = u => u === 'kg' ? 'kg' : 'und';

    function pintarResultados() {
        res.innerHTML = encontrados.map((p, i) => `
            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 ${i === idx ? 'active' : ''}" data-i="${i}">
                <img src="${esc(p.foto)}" alt="">
                <div><div class="fw-semibold">${esc(p.nombre)}</div><div class="small text-muted">${esc(p.codigo)} · ${esc(p.categoria)}${p.tipoCorte ? ' · ' + esc(p.tipoCorte) : ''}</div></div>
                <div class="ms-auto text-end"><div class="fw-semibold">${MS.pesos(p.precio)}/${uni(p.unidad)}</div>
                <div class="small ${p.stock > 0 ? 'text-muted' : 'text-danger'}">Disp.: ${MS.cant(p.stock, p.unidad)}</div></div>
            </button>`).join('');
    }

    async function traer(params) {
        const r = await fetch(URL_BUSCAR + '?' + new URLSearchParams(params), { headers: { Accept: 'application/json' } });
        return r.json();
    }

    async function buscar() {
        const q = input.value.trim();
        if (!q) { encontrados = []; pintarResultados(); return; }
        encontrados = await traer({ q }); idx = encontrados.length ? 0 : -1; pintarResultados();
    }

    let catalogo = [];
    async function cargarGrilla() {
        if (!grilla) return;
        grilla.innerHTML = '<div class="text-muted small p-2">Cargando productos…</div>';
        catalogo = await traer(catActual ? { categoria: catActual } : {});
        grilla.innerHTML = catalogo.length ? catalogo.map((p, i) => {
            const agotado = validarStock && p.stock <= 0;
            return `<button type="button" class="pos-tile ${agotado ? 'sin-stock' : ''}" data-g="${i}" ${agotado ? 'aria-disabled="true"' : ''} title="${esc(p.nombre)} · disp. ${MS.cant(p.stock, p.unidad)}">
                <img src="${esc(p.foto)}" alt="" loading="lazy">
                <div class="t-body"><div class="t-nombre">${esc(p.nombre)}</div><div class="t-precio">${MS.pesos(p.precio)}/${uni(p.unidad)}</div></div>
            </button>`;
        }).join('') : '<div class="text-muted small p-2">No hay productos en esta categoría.</div>';
    }

    function agregar(p, cantidad = null) {
        let l = lineas.find(x => x.id === p.id);
        if (!l) { l = { ...p, cantidad: cantidad ?? (p.unidad === 'kg' ? '' : 1) }; lineas.push(l); }
        else if (cantidad) { l.cantidad = Number(l.cantidad || 0) + Number(cantidad); }
        else if (p.unidad !== 'kg') { l.cantidad = Number(l.cantidad || 0) + 1; }
        input.value = ''; encontrados = []; pintarResultados(); pintar();
        const campo = tbody.querySelector(`tr[data-id="${p.id}"] .cant`);
        if (campo) { campo.focus(); campo.select(); }
    }

    function pintar() {
        const cols = compacto ? 4 : 5;
        if (!lineas.length) {
            tbody.innerHTML = `<tr class="vacio"><td colspan="${cols}" class="text-muted text-center py-4"><i class="bi bi-basket fs-3 d-block mb-1"></i>${validarStock ? 'Toque un producto o escanee su código.' : 'Agregue los productos del pedido.'}</td></tr>`;
        } else {
            tbody.innerHTML = lineas.map((l, i) => {
                const excede = validarStock && Number(l.cantidad) > l.stock;
                return `<tr data-id="${l.id}" class="${excede ? 'row-danger' : ''}">
                    <td><div class="con-foto"><img src="${esc(l.foto)}" alt="" class="thumb ${compacto ? 'thumb-sm' : ''}"><div>
                        <div class="fw-semibold lh-sm">${esc(l.nombre)}</div>
                        <div class="small text-muted">${compacto ? MS.pesos(l.precio) + '/' + uni(l.unidad) : esc(l.codigo)}${validarStock && !compacto ? ' · disp. ' + MS.cant(l.stock, l.unidad) : ''}</div>
                        ${excede ? '<div class="small text-danger fw-semibold">Supera el disponible (' + MS.cant(l.stock, l.unidad) + ')</div>' : ''}
                    </div></div>
                    <input type="hidden" name="items[${i}][producto_id]" value="${l.id}"></td>
                    <td><div class="input-group input-group-sm"><input type="number" class="form-control cant pos-qty" name="items[${i}][cantidad]" value="${l.cantidad}"
                        step="${l.unidad === 'kg' ? '0.001' : '1'}" min="${l.unidad === 'kg' ? '0.001' : '1'}" required data-i="${i}" aria-label="Cantidad de ${esc(l.nombre)}">
                        <span class="input-group-text">${uni(l.unidad)}</span></div></td>
                    ${compacto ? '' : `<td class="num">${MS.pesos(l.precio)}</td>`}
                    <td class="num fw-semibold sub">${MS.pesos(MS.subtotal(l.cantidad || 0, l.precio))}</td>
                    <td class="text-end"><button type="button" class="btn btn-sm btn-light quitar" data-i="${i}" title="Quitar"><i class="bi bi-x-lg"></i></button></td>
                </tr>`;
            }).join('');
        }
        totales();
    }

    function totales() {
        const sub = lineas.reduce((s, l) => s + MS.subtotal(l.cantidad || 0, l.precio), 0);
        document.getElementById('total').textContent = MS.pesos(sub);
        document.dispatchEvent(new CustomEvent('carrito', { detail: { subtotal: sub, lineas } }));
    }

    input.addEventListener('input', MS.debounce(buscar, 200));
    input.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') { idx = Math.min(idx + 1, encontrados.length - 1); pintarResultados(); e.preventDefault(); }
        if (e.key === 'ArrowUp') { idx = Math.max(idx - 1, 0); pintarResultados(); e.preventDefault(); }
        if (e.key === 'Enter') { e.preventDefault(); if (encontrados[idx]) agregar(encontrados[idx]); }
        if (e.key === 'Escape') { encontrados = []; pintarResultados(); }
    });
    res.addEventListener('click', e => { const b = e.target.closest('[data-i]'); if (b) agregar(encontrados[b.dataset.i]); });
    grilla?.addEventListener('click', e => {
        const b = e.target.closest('[data-g]');
        if (b && !b.classList.contains('sin-stock')) agregar(catalogo[b.dataset.g]);
    });
    document.getElementById('chipsCat')?.addEventListener('click', e => {
        const b = e.target.closest('[data-cat]'); if (!b) return;
        document.querySelectorAll('#chipsCat .chip').forEach(c => c.classList.toggle('active', c === b));
        catActual = b.dataset.cat; cargarGrilla();
    });
    tbody.addEventListener('input', e => {
        if (!e.target.classList.contains('cant')) return;
        const l = lineas[e.target.dataset.i]; l.cantidad = e.target.value;
        const tr = e.target.closest('tr');
        tr.querySelector('.sub').textContent = MS.pesos(MS.subtotal(l.cantidad || 0, l.precio));
        if (validarStock) tr.classList.toggle('row-danger', Number(l.cantidad) > l.stock);
        totales();
    });
    tbody.addEventListener('change', e => { if (e.target.classList.contains('cant')) pintar(); });
    tbody.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.classList.contains('cant')) { e.preventDefault(); input.focus(); } });
    tbody.addEventListener('click', e => { const b = e.target.closest('.quitar'); if (b) { lineas.splice(b.dataset.i, 1); pintar(); } });

    cargarGrilla();
    return { agregar, lineas: () => lineas, pintar };
})();
</script>
@php($precargar = old('items') ? collect(old('items'))->map(fn($i) => ['p' => \App\Models\Producto::with('categoria')->find($i['producto_id']), 'c' => $i['cantidad']]) : ($precargaPedido ?? collect()))
@if($precargar->count())
<script>
@foreach($precargar as $x) @if($x['p'])
Carrito.agregar({ id: {{ $x['p']->idProducto }}, codigo: @json($x['p']->codigo), nombre: @json($x['p']->nombre), categoria: '', unidad: @json($x['p']->unidadVenta), precio: {{ (float) $x['p']->precioVenta }}, stock: {{ $x['p']->stockVendible() }}, foto: @json($x['p']->fotoUrl()) }, {{ (float) $x['c'] }});
@endif @endforeach
document.getElementById('buscarProd').focus();
</script>
@endif
