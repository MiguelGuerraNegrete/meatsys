<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    protected $table = 'venta';

    protected $primaryKey = 'idVenta';

    public $timestamps = false;

    protected $fillable = [
        'Cliente_idCliente', 'Usuario_idUsuario', 'Caja_idCaja', 'fechaHora',
        'subtotal', 'impuesto', 'total', 'metodoPago',
    ];

    protected function casts(): array
    {
        return [
            'fechaHora' => 'datetime',
            'subtotal' => 'decimal:2',
            'impuesto' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'Cliente_idCliente');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Usuario_idUsuario');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'Caja_idCaja');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'Venta_idVenta');
    }

    public function factura(): HasOne
    {
        return $this->hasOne(Factura::class, 'Venta_idVenta');
    }

    /**
     * Agrupa los detalles por producto para mostrar en factura
     * (una línea puede haberse descontado de varios lotes).
     */
    public function lineasAgrupadas()
    {
        return $this->detalles->groupBy('Producto_idProducto')->map(function ($grupo) {
            $primero = $grupo->first();

            return (object) [
                'producto' => $primero->producto,
                'cantidad' => round($grupo->sum(fn ($d) => (float) $d->cantidad), 3),
                'precioUnitario' => (float) $primero->precioUnitario,
                'subtotal' => round($grupo->sum(fn ($d) => (float) $d->subtotal), 2),
            ];
        })->values();
    }
}
