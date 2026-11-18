<?php

namespace Database\Seeders;

use App\Models\AlertaVencimiento;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AlertaService;
use App\Services\CajaService;
use App\Services\CompraService;
use App\Services\PagoProveedorService;
use App\Services\VentaService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de demostración: usuarios por rol, catálogo, proveedores, compras con lotes,
 * dos semanas de ventas con cierres de caja, clientes y pedidos.
 * Uso: php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DatabaseSeeder::class);
        mt_srand(2026);

        $admin = Usuario::where('usuario', 'admin')->first();
        $cajero = Usuario::firstOrCreate(['usuario' => 'cajero'], [
            'Rol_idRol' => Rol::where('nombre', Rol::CAJERO)->value('idRol'), 'documento' => '1000000002',
            'nombres' => 'Laura', 'apellidos' => 'Pérez', 'email' => 'cajero@meatsys.local',
            'contrasena' => 'Cajero12345', 'telefono' => '3001234567', 'fechaCreacion' => now()->toDateString(),
        ]);
        $bodega = Usuario::firstOrCreate(['usuario' => 'inventario'], [
            'Rol_idRol' => Rol::where('nombre', Rol::INVENTARIO)->value('idRol'), 'documento' => '1000000003',
            'nombres' => 'Carlos', 'apellidos' => 'Martínez', 'email' => 'inventario@meatsys.local',
            'contrasena' => 'Inventario12345', 'telefono' => '3007654321', 'fechaCreacion' => now()->toDateString(),
        ]);

        $cats = [];
        foreach ([
            'Res' => 'Cortes de carne de res', 'Cerdo' => 'Cortes de cerdo', 'Pollo' => 'Pollo entero y presas',
            'Embutidos' => 'Chorizos, salchichas y similares', 'Otros' => 'Huevos y productos varios',
        ] as $n => $d) {
            $foto = 'img/categorias/'.Str::slug($n).'.jpg';
            $cats[$n] = Categoria::firstOrCreate(['nombre' => $n], ['descripcion' => $d, 'imagen' => is_file(public_path($foto)) ? $foto : null])->idCategoria;
        }

        // [código, nombre, categoría, corte, unidad, precio venta, costo, stock mínimo]
        $catalogo = [
            ['RES-001', 'Lomo fino de res', 'Res', 'Lomo', 'kg', 42000, 31000, 5],
            ['RES-002', 'Costilla de res', 'Res', 'Costilla', 'kg', 22000, 15500, 8],
            ['RES-003', 'Carne molida de res', 'Res', 'Molida', 'kg', 24000, 17000, 10],
            ['RES-004', 'Punta de anca', 'Res', 'Punta de anca', 'kg', 38000, 28000, 4],
            ['RES-005', 'Sobrebarriga', 'Res', 'Sobrebarriga', 'kg', 26000, 18500, 5],
            ['CER-001', 'Lomo de cerdo', 'Cerdo', 'Lomo', 'kg', 23000, 16000, 6],
            ['CER-002', 'Costilla de cerdo', 'Cerdo', 'Costilla', 'kg', 20000, 14000, 6],
            ['CER-003', 'Tocino', 'Cerdo', 'Tocino', 'kg', 16000, 10500, 3],
            ['POL-001', 'Pechuga de pollo', 'Pollo', 'Pechuga', 'kg', 17000, 11800, 8],
            ['POL-002', 'Muslos de pollo', 'Pollo', 'Muslo', 'kg', 13000, 8900, 6],
            ['POL-003', 'Pollo entero', 'Pollo', 'Entero', 'unidad', 24000, 17500, 10],
            ['EMB-001', 'Chorizo santarrosano (paquete x5)', 'Embutidos', null, 'unidad', 12000, 8000, 10],
            ['EMB-002', 'Salchicha ranchera (paquete)', 'Embutidos', null, 'unidad', 9500, 6200, 8],
            ['OTR-001', 'Huevos AA (cubeta x30)', 'Otros', null, 'unidad', 18000, 14000, 5],
        ];
        $productos = [];
        foreach ($catalogo as [$cod, $nom, $cat, $corte, $uni, $precio, $costo, $min]) {
            $p = Producto::firstOrCreate(['codigo' => $cod], [
                'Categoria_idCategoria' => $cats[$cat], 'nombre' => $nom, 'tipoCorte' => $corte, 'unidadVenta' => $uni,
                'precioVenta' => $precio, 'stockActual' => 0, 'stockMinimo' => $min, 'activo' => true,
                'imagen' => is_file(public_path("img/productos/$cod.jpg")) ? "img/productos/$cod.jpg" : null,
            ]);
            $productos[] = ['p' => $p, 'costo' => $costo];
        }

        $provs = collect([
            ['900123456-1', 'Frigorífico del Sinú S.A.S.', '6047851234', 'ventas@frigosinu.co', 'Km 3 vía Cereté, Montería'],
            ['900654321-7', 'Avícola La Granja Ltda.', '6047859876', 'pedidos@lagranja.co', 'Calle 41 #12-30, Montería'],
            ['800111222-3', 'Embutidos Córdoba', '3105550000', null, 'Cra 6 #30-15, Montería'],
        ])->map(fn ($v) => Proveedor::firstOrCreate(['nit' => $v[0]], ['razonSocial' => $v[1], 'telefono' => $v[2], 'email' => $v[3], 'direccion' => $v[4], 'activo' => true]));

        $clientes = collect([
            ['1067000111', 'María Fernanda López', '3014445566', 'mafe.lopez@example.com'],
            ['1067000222', 'Restaurante El Fogón', '3029998877', 'compras@elfogon.example.com'],
            ['1067000333', 'José Luis Ramírez', '3112223344', null],
            ['1067000444', 'Ana Sofía Herrera', '3158887766', 'ana.herrera@example.com'],
        ])->map(fn ($c) => Cliente::firstOrCreate(['documento' => $c[0]], ['nombre' => $c[1], 'telefono' => $c[2], 'email' => $c[3], 'fechaRegistro' => now()->subDays(20)->toDateString()]));

        $compras = app(CompraService::class);
        $ventas = app(VentaService::class);
        $caja = app(CajaService::class);
        $pagos = app(PagoProveedorService::class);

        $proveedorDe = fn ($p) => match ($p->Categoria_idCategoria) {
            $cats['Pollo'], $cats['Otros'] => $provs[1],
            $cats['Embutidos'] => $provs[2],
            default => $provs[0],
        };

        // Compras cada 5 días durante 15 días + ventas diarias
        Carbon::setTestNow();
        $hoy = now()->toImmutable();
        for ($dia = 15; $dia >= 0; $dia--) {
            $fecha = Carbon::instance($hoy->subDays($dia));

            if ($dia % 5 === 0) {
                Carbon::setTestNow($fecha->copy()->setTime(6, 30));
                foreach (collect($productos)->groupBy(fn ($x) => $proveedorDe($x['p'])->idProveedor) as $provId => $grupo) {
                    $items = $grupo->map(fn ($x) => [
                        'producto_id' => $x['p']->idProducto,
                        'cantidad' => $x['p']->unidadVenta === 'kg' ? mt_rand(7, 14) + mt_rand(0, 999) / 1000 : mt_rand(6, 14),
                        'precio_unitario' => $x['costo'],
                        // la última compra deja lotes que vencen pronto para mostrar alertas
                        'fecha_vencimiento' => $fecha->copy()->addDays($dia === 0 ? mt_rand(1, 6) : mt_rand(8, 20))->toDateString(),
                    ])->values()->all();
                    $c = $compras->registrar(['proveedor_id' => $provId, 'numero_factura' => 'FP-'.mt_rand(1000, 9999), 'fecha_compra' => now()->toDateTimeString(), 'items' => $items], $bodega);
                    if ($dia > 0) {
                        $pagos->registrar($c, $dia > 5 ? (float) $c->total : round((float) $c->total / 2, -3), 'transferencia', $admin);
                    }
                }
            }

            if ($dia === 0) {
                Carbon::setTestNow($hoy->setTime(7, 0));
                $caja->abrir($cajero, 200000);
                Carbon::setTestNow();
                break;
            }

            Carbon::setTestNow($fecha->copy()->setTime(8, 0));
            $c = $caja->abrir($cajero, 200000);
            $nVentas = mt_rand(8, 14);
            for ($v = 0; $v < $nVentas; $v++) {
                Carbon::setTestNow($fecha->copy()->setTime(8 + $v, mt_rand(0, 59)));
                $lineas = collect($productos)->random(mt_rand(1, 3))->map(fn ($x) => [
                    'producto_id' => $x['p']->idProducto,
                    'cantidad' => $x['p']->unidadVenta === 'kg' ? mt_rand(250, 2500) / 1000 : mt_rand(1, 2),
                ])->filter(fn ($l) => Producto::find($l['producto_id'])->stockVendible() >= $l['cantidad'])->values()->all();
                if (! $lineas) {
                    continue;
                }
                $ventas->registrar([
                    'cliente_id' => mt_rand(0, 2) ? null : $clientes->random()->idCliente,
                    'metodo_pago' => ['efectivo', 'efectivo', 'efectivo', 'tarjeta', 'transferencia'][mt_rand(0, 4)],
                    'items' => $lineas,
                ], $cajero);
            }
            Carbon::setTestNow($fecha->copy()->setTime(15, 0));
            $caja->registrarMovimiento($c, 'egreso', 'Compra de bolsas y papel', 15000);
            Carbon::setTestNow($fecha->copy()->setTime(18, 30));
            $esperado = (float) $c->fresh()->montoInicial + (float) $c->fresh()->totalIngresos - (float) $c->fresh()->totalEgresos;
            $diferencia = [0, 0, 0, -2000, 1000][mt_rand(0, 4)];
            $caja->cerrar($c, $esperado + $diferencia, $diferencia ? 'Diferencia detectada en el conteo' : null);
        }
        Carbon::setTestNow();

        Pedido::firstOrCreate(['Cliente_idCliente' => $clientes[1]->idCliente, 'estado' => 'pendiente'], [
            'Usuario_idUsuario' => $cajero->idUsuario, 'fechaPedido' => now(), 'fechaEntrega' => now()->addDay()->setTime(10, 0),
            'observaciones' => 'Costilla cortada en trozos de 5 cm, empacar al vacío',
        ])->detalles()->createMany([
            ['Producto_idProducto' => $productos[1]['p']->idProducto, 'cantidad' => 6],
            ['Producto_idProducto' => $productos[0]['p']->idProducto, 'cantidad' => 3.5],
        ]);

        // Simula que los lotes ya vencidos fueron retirados; quedan pendientes los próximos a vencer
        $alertas = app(AlertaService::class);
        $alertas->generar();
        AlertaVencimiento::with('lote')->where('estado', 'pendiente')->get()
            ->filter(fn ($a) => $a->lote->diasRestantes() < 0)
            ->each(fn ($a) => $alertas->atender($a, 'retiro', 'Producto vencido retirado del exhibidor'));
    }
}
