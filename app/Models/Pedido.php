<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    public const ESTADOS = ['pendiente', 'listo', 'entregado', 'cancelado'];

    protected $table = 'pedido';

    protected $primaryKey = 'idPedido';

    public $timestamps = false;

    protected $fillable = ['Cliente_idCliente', 'Usuario_idUsuario', 'fechaPedido', 'fechaEntrega', 'estado', 'observaciones'];

    protected function casts(): array
    {
        return ['fechaPedido' => 'datetime', 'fechaEntrega' => 'datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Cliente_idCliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Usuario_idUsuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'Pedido_idPedido');
    }

    /** Valor estimado con los precios vigentes (el pedido no guarda precios). */
    public function valorEstimado(): float
    {
        return round($this->detalles->sum(fn ($d) => (float) $d->cantidad * (float) $d->producto->precioVenta), 2);
    }
}
