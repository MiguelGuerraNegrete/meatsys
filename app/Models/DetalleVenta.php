<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';

    protected $primaryKey = 'idDetalleVenta';

    public $timestamps = false;

    protected $fillable = ['Venta_idVenta', 'Producto_idProducto', 'Lote_idLote', 'cantidad', 'precioUnitario', 'subtotal'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:3', 'precioUnitario' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Venta_idVenta');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Producto_idProducto');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'Lote_idLote');
    }
}
