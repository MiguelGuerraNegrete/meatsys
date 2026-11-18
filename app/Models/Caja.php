<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $table = 'caja';

    protected $primaryKey = 'idCaja';

    public $timestamps = false;

    protected $fillable = [
        'Usuario_idUsuario', 'fechaApertura', 'fechaCierre', 'montoInicial', 'totalIngresos',
        'totalEgresos', 'saldoFinal', 'montoContado', 'estado', 'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fechaApertura' => 'datetime',
            'fechaCierre' => 'datetime',
            'montoInicial' => 'decimal:2',
            'totalIngresos' => 'decimal:2',
            'totalEgresos' => 'decimal:2',
            'saldoFinal' => 'decimal:2',
            'montoContado' => 'decimal:2',
        ];
    }

    public static function abierta(): ?self
    {
        return static::where('estado', 'abierta')->latest('fechaApertura')->first();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'Usuario_idUsuario');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'Caja_idCaja');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'Caja_idCaja');
    }
}
