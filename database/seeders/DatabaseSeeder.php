<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Datos mínimos: los tres roles y el administrador inicial.
 * Para datos de demostración: php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys(Rol::ETIQUETAS) as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }

        Usuario::firstOrCreate(['usuario' => 'admin'], [
            'Rol_idRol' => Rol::where('nombre', Rol::ADMIN)->value('idRol'),
            'documento' => '1000000001',
            'nombres' => 'Administrador',
            'apellidos' => 'MeatSys',
            'email' => 'admin@meatsys.local',
            'contrasena' => 'Admin12345',
            'activo' => true,
            'fechaCreacion' => now()->toDateString(),
        ]);
    }
}
