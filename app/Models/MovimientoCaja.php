<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    protected $table = 'movimiento_caja';

    protected $primaryKey = 'idMovimiento';

    public $timestamps = false;

    protected $fillable = ['Caja_idCaja', 'tipo', 'concepto', 'monto', 'fechaHora'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'fechaHora' => 'datetime'];
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'Caja_idCaja');
    }
}
