<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categoria';

    protected $primaryKey = 'idCategoria';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'imagen'];

    public function fotoUrl(): string
    {
        return $this->imagen && is_file(public_path($this->imagen)) ? asset($this->imagen) : asset('img/sin-foto.svg');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'Categoria_idCategoria');
    }
}
