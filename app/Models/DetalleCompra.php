<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compra';

    protected $primaryKey = 'idDetalleCompra';

    public $timestamps = false;

    protected $fillable = ['Compra_idCompra', 'Producto_idProducto', 'cantidad', 'precioUnitario', 'subtotal'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:3', 'precioUnitario' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'Compra_idCompra');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Producto_idProducto');
    }

    public function lote(): HasOne
    {
        return $this->hasOne(Lote::class, 'DetalleCompra_idDetalleCompra');
    }
}
