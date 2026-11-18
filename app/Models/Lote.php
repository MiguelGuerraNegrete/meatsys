<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    protected $table = 'lote';

    protected $primaryKey = 'idLote';

    public $timestamps = false;

    protected $fillable = [
        'Producto_idProducto', 'DetalleCompra_idDetalleCompra', 'cantidadInicial',
        'cantidadDisponible', 'fechaIngreso', 'fechaVencimiento',
    ];

    protected function casts(): array
    {
        return [
            'cantidadInicial' => 'decimal:3',
            'cantidadDisponible' => 'decimal:3',
            'fechaIngreso' => 'date',
            'fechaVencimiento' => 'date',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'Producto_idProducto');
    }

    public function detalleCompra(): BelongsTo
    {
        return $this->belongsTo(DetalleCompra::class, 'DetalleCompra_idDetalleCompra');
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(AlertaVencimiento::class, 'Lote_idLote');
    }

    public function diasRestantes(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->fechaVencimiento->copy()->startOfDay(), false);
    }
}
