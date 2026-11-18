<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    protected $table = 'factura';

    protected $primaryKey = 'idFactura';

    public $timestamps = false;

    protected $fillable = ['Venta_idVenta', 'numeroFactura', 'fechaEmision', 'rutaPdf'];

    protected function casts(): array
    {
        return ['fechaEmision' => 'datetime'];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'Venta_idVenta');
    }
}
