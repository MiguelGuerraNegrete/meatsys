<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Compra;
use App\Models\PagoProveedor;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** RF7: pagos a proveedores con descuento del saldo pendiente. */
class PagoProveedorService
{
    public function __construct(private CajaService $caja) {}

    public function registrar(Compra $compra, float $monto, string $metodo, Usuario $usuario, ?string $fecha = null): PagoProveedor
    {
        return DB::transaction(function () use ($compra, $monto, $metodo, $usuario, $fecha) {
            $compra = Compra::lockForUpdate()->findOrFail($compra->idCompra);
            $monto = Cantidad::dinero($monto);
            $saldo = (float) $compra->saldoPendiente;

            if ($monto <= 0) {
                throw ValidationException::withMessages(['monto' => 'El monto debe ser mayor que cero.']);
            }
            if ($monto - $saldo > 0.001) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto no puede superar el saldo pendiente ($'.number_format($saldo, 2, ',', '.').').',
                ]);
            }

            $pago = $compra->pagos()->create([
                'Usuario_idUsuario' => $usuario->idUsuario,
                'fechaPago' => $fecha ? Carbon::parse($fecha) : now(),
                'monto' => $monto,
                'metodoPago' => $metodo,
            ]);

            $nuevoSaldo = round($saldo - $monto, 2);
            $compra->update([
                'saldoPendiente' => $nuevoSaldo,
                'estadoPago' => $nuevoSaldo <= 0 ? 'pagado' : 'parcial',
            ]);

            // Un pago en efectivo sale de la caja abierta como egreso
            if ($metodo === 'efectivo' && ($caja = Caja::abierta())) {
                $this->caja->registrarMovimiento(
                    $caja, 'egreso',
                    'Pago a proveedor '.$compra->proveedor->razonSocial.' (compra #'.$compra->idCompra.')',
                    $monto
                );
            }

            return $pago;
        });
    }
}
