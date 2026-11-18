<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'cliente';

    protected $primaryKey = 'idCliente';

    public $timestamps = false;

    protected $fillable = ['documento', 'nombre', 'telefono', 'email', 'direccion', 'fechaRegistro'];

    protected function casts(): array
    {
        return ['fechaRegistro' => 'date'];
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'Cliente_idCliente');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'Cliente_idCliente');
    }
}
