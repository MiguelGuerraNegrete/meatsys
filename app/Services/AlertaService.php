<?php

namespace App\Services;

use App\Models\AlertaVencimiento;
use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

/** RF6: control de vencimientos por lote. */
class AlertaService
{
    /**
     * Revisa los lotes con saldo y crea/actualiza alertas para los que vencen
     * dentro de los días configurados (incluye los ya vencidos con saldo).
     *
     * @return int número de alertas pendientes tras la revisión
     */
    public function generar(?int $dias = null): int
    {
        $dias ??= config('meatsys.dias_alerta_vencimiento');
        $limite = now()->addDays($dias)->toDateString();

        $lotes = Lote::where('cantidadDisponible', '>', 0)
            ->whereDate('fechaVencimiento', '<=', $limite)
            ->get();

        foreach ($lotes as $lote) {
            $alerta = AlertaVencimiento::where('Lote_idLote', $lote->idLote)->where('estado', 'pendiente')->first();
            if ($alerta) {
                $alerta->update(['diasRestantes' => $lote->diasRestantes()]);
            } elseif (! AlertaVencimiento::where('Lote_idLote', $lote->idLote)->where('estado', 'atendida')->exists()) {
                AlertaVencimiento::create([
                    'Lote_idLote' => $lote->idLote,
                    'fechaGeneracion' => now(),
                    'diasRestantes' => $lote->diasRestantes(),
                    'estado' => 'pendiente',
                ]);
            }
        }

        return AlertaVencimiento::where('estado', 'pendiente')->count();
    }

    /** Marca la alerta como atendida; si la acción es retiro, ajusta el stock. */
    public function atender(AlertaVencimiento $alerta, string $accion, ?string $detalle = null): void
    {
        DB::transaction(function () use ($alerta, $accion, $detalle) {
            $texto = ucfirst($accion).($detalle ? ': '.$detalle : '');

            if ($accion === 'retiro') {
                $lote = Lote::lockForUpdate()->findOrFail($alerta->Lote_idLote);
                $retirado = Cantidad::aMilesimas($lote->cantidadDisponible);
                if ($retirado > 0) {
                    $producto = Producto::lockForUpdate()->findOrFail($lote->Producto_idProducto);
                    $producto->stockActual = Cantidad::deMilesimas(max(0, Cantidad::aMilesimas($producto->stockActual) - $retirado));
                    $producto->save();
                    $lote->cantidadDisponible = 0;
                    $lote->save();
                    $texto .= ' (retirados '.number_format($retirado / 1000, 3, ',', '.').' '.$producto->unidadCorta().')';
                }
            }

            $alerta->update(['estado' => 'atendida', 'accionTomada' => mb_substr($texto, 0, 200)]);
        });
    }
}
