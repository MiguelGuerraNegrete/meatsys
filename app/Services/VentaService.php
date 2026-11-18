<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RF2 / RF5: registra la venta, calcula el total según peso o cantidad,
 * descuenta el stock de los lotes más antiguos (primero el que vence antes)
 * y registra el ingreso en la caja abierta.
 */
class VentaService
{
    public function __construct(
        private CajaService $caja,
        private FacturaService $facturas,
    ) {}

    /**
     * @param  array{cliente_id:?int, metodo_pago:string, pedido_id:?int,
     *               items: array<int, array{producto_id:int, cantidad:float}>}  $datos
     */
    public function registrar(array $datos, Usuario $usuario): Venta
    {
        $caja = Caja::abierta();
        if (! $caja) {
            throw ValidationException::withMessages(['caja' => 'No hay una caja abierta. Abra la caja antes de vender.']);
        }

        // Consolidar líneas repetidas del mismo producto
        $items = [];
        foreach ($datos['items'] as $item) {
            $id = (int) $item['producto_id'];
            $items[$id] = ($items[$id] ?? 0) + Cantidad::aMilesimas($item['cantidad']);
        }

        $venta = DB::transaction(function () use ($items, $datos, $usuario, $caja) {
            $venta = Venta::create([
                'Cliente_idCliente' => $datos['cliente_id'] ?? null,
                'Usuario_idUsuario' => $usuario->idUsuario,
                'Caja_idCaja' => $caja->idCaja,
                'fechaHora' => now(),
                'subtotal' => 0,
                'impuesto' => 0,
                'total' => 0,
                'metodoPago' => $datos['metodo_pago'],
            ]);

            $subtotalVenta = 0.0;
            foreach ($items as $productoId => $milesimas) {
                $producto = Producto::lockForUpdate()->findOrFail($productoId);

                if (! $producto->activo) {
                    throw ValidationException::withMessages(['items' => "El producto {$producto->nombre} no está disponible para la venta."]);
                }
                if ($milesimas <= 0) {
                    throw ValidationException::withMessages(['items' => "Cantidad inválida para {$producto->nombre}."]);
                }
                if ($producto->unidadVenta === 'unidad' && $milesimas % 1000 !== 0) {
                    throw ValidationException::withMessages(['items' => "{$producto->nombre} se vende por unidad: use cantidades enteras."]);
                }

                $lotes = Lote::where('Producto_idProducto', $productoId)
                    ->where('cantidadDisponible', '>', 0)
                    ->whereDate('fechaVencimiento', '>=', now()->toDateString())
                    ->orderBy('fechaVencimiento')->orderBy('fechaIngreso')->orderBy('idLote')
                    ->lockForUpdate()->get();

                $disponible = $lotes->sum(fn ($l) => Cantidad::aMilesimas($l->cantidadDisponible));
                if ($milesimas > $disponible) {
                    throw ValidationException::withMessages([
                        'items' => sprintf(
                            'Stock insuficiente de %s: solicitado %s %s, disponible %s %s.',
                            $producto->nombre,
                            number_format($milesimas / 1000, 3, ',', '.'), $producto->unidadCorta(),
                            number_format($disponible / 1000, 3, ',', '.'), $producto->unidadCorta(),
                        ),
                    ]);
                }

                $pendiente = $milesimas;
                foreach ($lotes as $lote) {
                    if ($pendiente <= 0) {
                        break;
                    }
                    $delLote = min($pendiente, Cantidad::aMilesimas($lote->cantidadDisponible));
                    $cantidad = Cantidad::deMilesimas($delLote);
                    $sub = Cantidad::subtotal($cantidad, $producto->precioVenta);

                    $venta->detalles()->create([
                        'Producto_idProducto' => $productoId,
                        'Lote_idLote' => $lote->idLote,
                        'cantidad' => $cantidad,
                        'precioUnitario' => $producto->precioVenta,
                        'subtotal' => $sub,
                    ]);

                    $lote->cantidadDisponible = Cantidad::deMilesimas(Cantidad::aMilesimas($lote->cantidadDisponible) - $delLote);
                    $lote->save();

                    $subtotalVenta += $sub;
                    $pendiente -= $delLote;
                }

                $producto->stockActual = Cantidad::deMilesimas(max(0, Cantidad::aMilesimas($producto->stockActual) - $milesimas));
                $producto->save();
            }

            $subtotalVenta = round($subtotalVenta, 2);
            $impuesto = round($subtotalVenta * config('meatsys.impuesto_porcentaje') / 100, 2);
            $total = round($subtotalVenta + $impuesto, 2);
            $venta->update(['subtotal' => $subtotalVenta, 'impuesto' => $impuesto, 'total' => $total]);

            // Solo el efectivo entra físicamente a la caja; tarjeta y transferencia
            // se muestran aparte en el cierre para no descuadrar el conteo de efectivo.
            if ($datos['metodo_pago'] === 'efectivo') {
                $this->caja->registrarMovimiento($caja, 'ingreso', 'Venta #'.$venta->idVenta, $total);
            }

            if (! empty($datos['pedido_id'])) {
                Pedido::whereKey($datos['pedido_id'])->update(['estado' => 'entregado']);
            }

            return $venta;
        });

        // RF3: la factura se genera al confirmar la venta
        $this->facturas->generar($venta);

        return $venta->fresh(['detalles.producto', 'factura', 'cliente']);
    }
}
