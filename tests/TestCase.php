<?php

namespace Tests;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CajaService;
use App\Services\CompraService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (in_array(RefreshDatabase::class, class_uses_recursive($this))) {
            $this->seed(DatabaseSeeder::class);
        }
        Storage::fake('local');
    }

    /**
     * Protección: las pruebas reconstruyen la base de datos, así que nunca
     * deben ejecutarse contra la base real del negocio.
     */
    protected function beforeRefreshingDatabase()
    {
        $c = config('database.default');
        if (config("database.connections.$c.driver") !== 'sqlite' || config("database.connections.$c.database") !== ':memory:') {
            throw new \RuntimeException('Las pruebas solo se ejecutan con SQLite en memoria. Revise phpunit.xml.');
        }
    }

    protected function usuario(string $rol = Rol::ADMIN, array $extra = []): Usuario
    {
        static $n = 100;
        $n++;

        return Usuario::create($extra + [
            'Rol_idRol' => Rol::where('nombre', $rol)->value('idRol'),
            'documento' => (string) (2000000 + $n), 'nombres' => 'Prueba', 'apellidos' => ucfirst($rol),
            'email' => "u{$n}@test.local", 'usuario' => "{$rol}{$n}", 'contrasena' => 'Clave12345',
            'activo' => true, 'fechaCreacion' => now()->toDateString(),
        ]);
    }

    protected function producto(array $extra = []): Producto
    {
        $cat = Categoria::firstOrCreate(['nombre' => 'Res']);

        return Producto::create($extra + [
            'Categoria_idCategoria' => $cat->idCategoria, 'codigo' => 'P'.uniqid(), 'nombre' => 'Lomo',
            'unidadVenta' => 'kg', 'precioVenta' => 40000, 'stockActual' => 0, 'stockMinimo' => 2, 'activo' => true,
        ]);
    }

    protected function proveedor(): Proveedor
    {
        return Proveedor::create(['nit' => (string) mt_rand(100000, 999999), 'razonSocial' => 'Frigorífico Prueba', 'activo' => true]);
    }

    /** Ingresa un lote del producto mediante el servicio de compras. */
    protected function ingresar(Producto $p, float $cantidad, string $vence, float $costo = 30000, ?string $fecha = null)
    {
        return app(CompraService::class)->registrar([
            'proveedor_id' => $this->proveedor()->idProveedor,
            'fecha_compra' => $fecha ?? now()->toDateTimeString(),
            'items' => [['producto_id' => $p->idProducto, 'cantidad' => $cantidad, 'precio_unitario' => $costo, 'fecha_vencimiento' => $vence]],
        ], Usuario::first());
    }

    protected function abrirCaja(float $monto = 100000): Caja
    {
        return app(CajaService::class)->abrir(Usuario::first(), $monto);
    }
}
