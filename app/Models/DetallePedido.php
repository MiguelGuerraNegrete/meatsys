<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedido';

    protected $primaryKey = 'idDetallePedido';

    public $timestamps = false;

    protected $fillable = ['Pedido_idPedido', 'Producto_idProducto', 'cantidad'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:3'];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'Pedido_idPedido');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Producto_idProducto');
    }
}
