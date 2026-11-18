<!doctype html>
<html><head><meta charset="utf-8">
<style>
    @page { margin: 10px 12px; }
    * { font-family: DejaVu Sans Mono, monospace; }
    body { font-size: 9px; color: #000; }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
    hr { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 1px 0; }
    .tot td { font-size: 11px; font-weight: bold; }
</style></head>
<body>
    <div class="c b" style="font-size:12px">{{ config('meatsys.negocio.nombre') }}</div>
    <div class="c">NIT {{ config('meatsys.negocio.nit') }}</div>
    <div class="c">{{ config('meatsys.negocio.direccion') }}</div>
    @if(config('meatsys.negocio.telefono'))<div class="c">Tel. {{ config('meatsys.negocio.telefono') }}</div>@endif
    <hr>
    <div class="b">FACTURA DE VENTA {{ $factura->numeroFactura }}</div>
    <div>Fecha: {{ $factura->fechaEmision->format('d/m/Y h:i a') }}</div>
    <div>Cajero: {{ $venta->usuario->nombres }}</div>
    <div>Cliente: {{ $venta->cliente?->nombre ?? 'Consumidor final' }}</div>
    @if($venta->cliente)<div>Doc.: {{ $venta->cliente->documento }}</div>@endif
    <hr>
    <table>
        @foreach($lineas as $l)
            <tr><td colspan="2">{{ $l->producto->nombre }}</td></tr>
            <tr><td>{{ cant($l->cantidad, $l->producto->unidadVenta) }} x {{ pesos($l->precioUnitario) }}</td><td class="r">{{ pesos($l->subtotal) }}</td></tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ pesos($venta->subtotal) }}</td></tr>
        <tr><td>Impuesto</td><td class="r">{{ pesos($venta->impuesto) }}</td></tr>
        <tr class="tot"><td>TOTAL</td><td class="r">{{ pesos($venta->total) }}</td></tr>
        <tr><td>Pago</td><td class="r">{{ ucfirst($venta->metodoPago) }}</td></tr>
    </table>
    <hr>
    <div class="c">¡Gracias por su compra!</div>
    <div class="c" style="font-size:7px">Generado por MeatSys</div>
</body></html>
