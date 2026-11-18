// Utilidades compartidas de MeatSys
window.MS = {
  pesos(v) {
    return '$' + Number(v || 0).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  },
  cant(v, unidad) {
    const dec = unidad === 'kg' ? 3 : 0;
    return Number(v || 0).toLocaleString('es-CO', { minimumFractionDigits: dec, maximumFractionDigits: 3 }) + (unidad ? ' ' + (unidad === 'kg' ? 'kg' : 'und') : '');
  },
  // Subtotal con la misma regla del servidor: milésimas × centavos, redondeo a centavos
  subtotal(cantidad, precio) {
    const m = Math.round(Number(cantidad) * 1000), c = Math.round(Number(precio) * 100);
    return Math.floor((m * c + 500) / 1000) / 100;
  },
  debounce(fn, ms = 250) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; },
};

// Formularios con confirmación: <form data-confirm="¿Seguro?">
document.addEventListener('submit', (e) => {
  const f = e.target;
  if (!f.dataset.confirm || f.dataset.confirmed) return;
  e.preventDefault();
  document.getElementById('confirmarTexto').textContent = f.dataset.confirm;
  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmar'));
  document.getElementById('confirmarOk').onclick = () => { f.dataset.confirmed = '1'; modal.hide(); f.requestSubmit ? f.requestSubmit() : f.submit(); };
  modal.show();
});

// Evitar doble envío en formularios
document.addEventListener('submit', (e) => {
  const f = e.target;
  if (f.dataset.confirm && !f.dataset.confirmed) return;
  if (e.defaultPrevented) return;
  f.querySelectorAll('button[type=submit], button:not([type])').forEach(b => { setTimeout(() => b.disabled = true, 0); });
});
