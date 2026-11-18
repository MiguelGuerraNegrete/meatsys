<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoProveedor extends Model
{
    protected $table = 'pago_proveedor';

    protected $primaryKey = 'idPagoProveedor';

    public $timestamps = false;

    protected $fillable = ['Compra_idCompra', 'Usuario_idUsuario', 'fechaPago', 'monto', 'metodoPago'];

    protected function casts(): array
    {
        return ['fechaPago' => 'datetime', 'monto' => 'decimal:2'];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'Compra_idCompra');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Usuario_idUsuario');
    }
}
