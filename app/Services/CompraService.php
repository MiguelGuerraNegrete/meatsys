<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RF4 / RF5: registra la compra a un proveedor, crea un lote por cada
 * producto recibido y aumenta el stock automáticamente.
 */
class CompraService
{
    /**
     * @param  array{proveedor_id:int, numero_factura:?string, fecha_compra:?string,
     *               items: array<int, array{producto_id:int, cantidad:float, precio_unitario:float, fecha_vencimiento:string}>}  $datos
     */
    public function registrar(array $datos, Usuario $usuario): Compra
    {
        $fechaCompra = isset($datos['fecha_compra']) ? Carbon::parse($datos['fecha_compra']) : now();
        $fechaIngreso = $fechaCompra->copy()->startOfDay();

        foreach ($datos['items'] as $i => $item) {
            if (Carbon::parse($item['fecha_vencimiento'])->startOfDay()->lte($fechaIngreso)) {
                throw ValidationException::withMessages([
                    "items.$i.fecha_vencimiento" => 'La fecha de vencimiento debe ser posterior a la fecha de ingreso.',
                ]);
            }
        }

        return DB::transaction(function () use ($datos, $usuario, $fechaCompra, $fechaIngreso) {
            $compra = Compra::create([
                'Proveedor_idProveedor' => $datos['proveedor_id'],
                'Usuario_idUsuario' => $usuario->idUsuario,
                'numeroFactura' => $datos['numero_factura'] ?? null,
                'fechaCompra' => $fechaCompra,
                'total' => 0,
                'saldoPendiente' => 0,
                'estadoPago' => 'pendiente',
            ]);

            $total = 0.0;
            foreach ($datos['items'] as $item) {
                $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);
                $cantidad = Cantidad::deMilesimas(Cantidad::aMilesimas($item['cantidad']));
                $subtotal = Cantidad::subtotal($cantidad, $item['precio_unitario']);

                $detalle = $compra->detalles()->create([
                    'Producto_idProducto' => $producto->idProducto,
                    'cantidad' => $cantidad,
                    'precioUnitario' => Cantidad::dinero($item['precio_unitario']),
                    'subtotal' => $subtotal,
                ]);

                Lote::create([
                    'Producto_idProducto' => $producto->idProducto,
                    'DetalleCompra_idDetalleCompra' => $detalle->idDetalleCompra,
                    'cantidadInicial' => $cantidad,
                    'cantidadDisponible' => $cantidad,
                    'fechaIngreso' => $fechaIngreso->toDateString(),
                    'fechaVencimiento' => Carbon::parse($item['fecha_vencimiento'])->toDateString(),
                ]);

                $producto->stockActual = Cantidad::deMilesimas(
                    Cantidad::aMilesimas($producto->stockActual) + Cantidad::aMilesimas($cantidad)
                );
                $producto->save();

                $total += $subtotal;
            }

            $compra->update(['total' => round($total, 2), 'saldoPendiente' => round($total, 2)]);

            return $compra->fresh(['detalles.producto', 'detalles.lote', 'proveedor']);
        });
    }
}
