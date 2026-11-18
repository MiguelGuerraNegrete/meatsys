<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** RF3: factura o recibo de venta en PDF con número consecutivo. */
class FacturaService
{
    public function generar(Venta $venta): Factura
    {
        $factura = $venta->factura ?: DB::transaction(function () use ($venta) {
            // Consecutivo estricto a partir del último número emitido (no del id autoincremental,
            // que en MySQL puede saltar números tras una transacción revertida).
            $ultimo = Factura::lockForUpdate()->orderByDesc('idFactura')->value('numeroFactura');
            $siguiente = $ultimo ? ((int) preg_replace('/\D/', '', substr($ultimo, strrpos($ultimo, '-') + 1))) + 1 : 1;
            $numero = sprintf('%s-%06d', config('meatsys.prefijo_factura'), $siguiente);

            return Factura::create([
                'Venta_idVenta' => $venta->idVenta,
                'numeroFactura' => $numero,
                'fechaEmision' => now(),
            ]);
        });

        $ruta = 'facturas/'.$factura->numeroFactura.'.pdf';
        Storage::disk('local')->put($ruta, $this->pdf($venta->fresh(['detalles.producto', 'cliente', 'usuario']), $factura)->output());
        $factura->update(['rutaPdf' => $ruta]);

        return $factura;
    }

    public function pdf(Venta $venta, Factura $factura)
    {
        $lineas = $venta->lineasAgrupadas();
        // Ancho de tirilla de 80 mm; alto según número de líneas
        $alto = 240 + 26 * $lineas->count();

        return Pdf::loadView('pdf.factura', compact('venta', 'factura', 'lineas'))
            ->setPaper([0, 0, 226.77, $alto]);
    }

    public function rutaAbsoluta(Factura $factura): string
    {
        if (! $factura->rutaPdf || ! Storage::disk('local')->exists($factura->rutaPdf)) {
            $this->generar($factura->venta);
            $factura->refresh();
        }

        return Storage::disk('local')->path($factura->rutaPdf);
    }
}
