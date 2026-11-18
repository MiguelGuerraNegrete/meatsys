<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $table = 'compra';

    protected $primaryKey = 'idCompra';

    public $timestamps = false;

    protected $fillable = [
        'Proveedor_idProveedor', 'Usuario_idUsuario', 'numeroFactura', 'fechaCompra',
        'total', 'saldoPendiente', 'estadoPago',
    ];

    protected function casts(): array
    {
        return [
            'fechaCompra' => 'datetime',
            'total' => 'decimal:2',
            'saldoPendiente' => 'decimal:2',
        ];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'Proveedor_idProveedor');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Usuario_idUsuario');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'Compra_idCompra');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoProveedor::class, 'Compra_idCompra');
    }
}
