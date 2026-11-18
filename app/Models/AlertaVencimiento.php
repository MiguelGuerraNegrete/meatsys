<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertaVencimiento extends Model
{
    protected $table = 'alerta_vencimiento';

    protected $primaryKey = 'idAlerta';

    public $timestamps = false;

    protected $fillable = ['Lote_idLote', 'fechaGeneracion', 'diasRestantes', 'estado', 'accionTomada'];

    protected function casts(): array
    {
        return ['fechaGeneracion' => 'datetime', 'diasRestantes' => 'integer'];
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'Lote_idLote');
    }
}
