<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuario';

    protected $primaryKey = 'idUsuario';

    public $timestamps = false;

    /** La columna de contraseña se llama "contrasena" según el modelo de datos. */
    protected $authPasswordName = 'contrasena';

    /** El esquema no tiene columna remember_token. */
    protected $rememberTokenName = '';

    protected $fillable = [
        'Rol_idRol', 'documento', 'nombres', 'apellidos', 'email', 'usuario',
        'contrasena', 'telefono', 'activo', 'resetCode', 'resetCodeExpiration', 'fechaCreacion',
    ];

    protected $hidden = ['contrasena', 'resetCode'];

    protected function casts(): array
    {
        return [
            'contrasena' => 'hashed',
            'activo' => 'boolean',
            'resetCodeExpiration' => 'datetime',
            'fechaCreacion' => 'date',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->contrasena;
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'Rol_idRol');
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol(Rol::ADMIN);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }
}
