<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedor';

    protected $primaryKey = 'idProveedor';

    public $timestamps = false;

    protected $fillable = ['nit', 'razonSocial', 'telefono', 'email', 'direccion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'Proveedor_idProveedor');
    }

    public function saldoPendiente(): float
    {
        return (float) $this->compras()->sum('saldoPendiente');
    }
}
