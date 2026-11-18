<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'producto';

    protected $primaryKey = 'idProducto';

    public $timestamps = false;

    protected $fillable = [
        'Categoria_idCategoria', 'codigo', 'nombre', 'tipoCorte', 'unidadVenta',
        'precioVenta', 'stockActual', 'stockMinimo', 'activo', 'imagen',
    ];

    protected function casts(): array
    {
        return [
            'precioVenta' => 'decimal:2',
            'stockActual' => 'decimal:3',
            'stockMinimo' => 'decimal:3',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'Categoria_idCategoria');
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class, 'Producto_idProducto');
    }

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('activo', true);
    }

    public function scopeBuscar(Builder $q, ?string $texto): Builder
    {
        if (! $texto) {
            return $q;
        }

        return $q->where(function ($q) use ($texto) {
            $q->where('nombre', 'like', "%{$texto}%")
                ->orWhere('codigo', 'like', "%{$texto}%")
                ->orWhere('tipoCorte', 'like', "%{$texto}%")
                ->orWhereHas('categoria', fn ($c) => $c->where('nombre', 'like', "%{$texto}%"));
        });
    }

    public function bajoMinimo(): bool
    {
        return (float) $this->stockActual <= (float) $this->stockMinimo;
    }

    /** Cantidad vendible: lotes con saldo que aún no han vencido. */
    public function stockVendible(): float
    {
        return (float) $this->lotes()
            ->where('cantidadDisponible', '>', 0)
            ->whereDate('fechaVencimiento', '>=', now()->toDateString())
            ->sum('cantidadDisponible');
    }

    /** Foto del producto; si no tiene, la de su categoría; si tampoco, una imagen genérica. */
    public function fotoUrl(): string
    {
        if ($this->imagen && is_file(public_path($this->imagen))) {
            return asset($this->imagen);
        }

        return $this->categoria?->fotoUrl() ?? asset('img/sin-foto.svg');
    }

    public function tieneFotoPropia(): bool
    {
        return $this->imagen && is_file(public_path($this->imagen));
    }

    public function unidadCorta(): string
    {
        return $this->unidadVenta === 'kg' ? 'kg' : 'und';
    }
}
