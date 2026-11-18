<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public const ADMIN = 'administrador';

    public const CAJERO = 'cajero';

    public const INVENTARIO = 'inventario';

    public const ETIQUETAS = [
        self::ADMIN => 'Administrador',
        self::CAJERO => 'Cajero/Vendedor',
        self::INVENTARIO => 'Encargado de inventario',
    ];

    protected $table = 'rol';

    protected $primaryKey = 'idRol';

    public $timestamps = false;

    protected $fillable = ['nombre'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'Rol_idRol');
    }

    public function etiqueta(): string
    {
        return self::ETIQUETAS[$this->nombre] ?? ucfirst($this->nombre);
    }
}
