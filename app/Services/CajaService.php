<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** RF9: apertura, movimientos y cierre de caja diario. */
class CajaService
{
    public function abrir(Usuario $usuario, float $montoInicial): Caja
    {
        if (Caja::abierta()) {
            throw ValidationException::withMessages(['montoInicial' => 'Ya hay una caja abierta. Ciérrela antes de abrir otra.']);
        }

        return Caja::create([
            'Usuario_idUsuario' => $usuario->idUsuario,
            'fechaApertura' => now(),
            'montoInicial' => Cantidad::dinero($montoInicial),
            'totalIngresos' => 0,
            'totalEgresos' => 0,
            'estado' => 'abierta',
        ]);
    }

    public function registrarMovimiento(Caja $caja, string $tipo, string $concepto, float $monto): MovimientoCaja
    {
        if ($caja->estado !== 'abierta') {
            throw ValidationException::withMessages(['caja' => 'La caja está cerrada.']);
        }

        $mov = $caja->movimientos()->create([
            'tipo' => $tipo,
            'concepto' => mb_substr($concepto, 0, 150),
            'monto' => Cantidad::dinero($monto),
            'fechaHora' => now(),
        ]);
        $this->recalcular($caja);

        return $mov;
    }

    /** Recalcula totales de ingresos/egresos a partir de los movimientos. */
    public function recalcular(Caja $caja): Caja
    {
        $ingresos = (float) $caja->movimientos()->where('tipo', 'ingreso')->sum('monto');
        $egresos = (float) $caja->movimientos()->where('tipo', 'egreso')->sum('monto');
        $caja->update(['totalIngresos' => round($ingresos, 2), 'totalEgresos' => round($egresos, 2)]);

        return $caja;
    }

    /** Resumen para la pantalla de cierre. */
    public function resumen(Caja $caja): array
    {
        $this->recalcular($caja);
        $ventasPorMetodo = $caja->ventas()
            ->select('metodoPago', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total'))
            ->groupBy('metodoPago')->get()->keyBy('metodoPago');

        $saldoEsperado = round((float) $caja->montoInicial + (float) $caja->totalIngresos - (float) $caja->totalEgresos, 2);

        return [
            'montoInicial' => (float) $caja->montoInicial,
            'totalIngresos' => (float) $caja->totalIngresos,
            'totalEgresos' => (float) $caja->totalEgresos,
            'saldoEsperado' => $saldoEsperado,
            'ventasPorMetodo' => $ventasPorMetodo,
            'numeroVentas' => $caja->ventas()->count(),
            'totalVentas' => (float) $caja->ventas()->sum('total'),
        ];
    }

    public function cerrar(Caja $caja, float $montoContado, ?string $observaciones): Caja
    {
        if ($caja->estado !== 'abierta') {
            throw ValidationException::withMessages(['caja' => 'Esta caja ya fue cerrada.']);
        }

        return DB::transaction(function () use ($caja, $montoContado, $observaciones) {
            $r = $this->resumen($caja);
            $caja->update([
                'fechaCierre' => now(),
                'saldoFinal' => $r['saldoEsperado'],
                'montoContado' => Cantidad::dinero($montoContado),
                'estado' => 'cerrada',
                'observaciones' => $observaciones,
            ]);

            return $caja->fresh();
        });
    }
}
