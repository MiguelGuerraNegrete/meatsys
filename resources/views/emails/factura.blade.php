<div style="font-family:Segoe UI,Arial,sans-serif;max-width:480px;margin:auto;color:#2b2522">
    <h2 style="color:#8f1d2c">{{ config('meatsys.negocio.nombre') }}</h2>
    <p>Gracias por su compra. Adjuntamos la factura <strong>{{ $factura->numeroFactura }}</strong> por un total de
        <strong>${{ number_format($factura->venta->total, 2, ',', '.') }}</strong>.</p>
</div>
