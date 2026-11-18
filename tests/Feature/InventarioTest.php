<?php

namespace Tests\Feature;

use App\Models\AlertaVencimiento;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Rol;
use App\Services\AlertaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_compra_crea_lote_y_aumenta_stock(): void
    {
        $p = $this->producto();
        $prov = $this->proveedor();

        $this->actingAs($this->usuario(Rol::INVENTARIO))->post('/compras', [
            'proveedor_id' => $prov->idProveedor,
            'fecha_compra' => now()->format('Y-m-d H:i'),
            'items' => [
                ['producto_id' => $p->idProducto, 'cantidad' => 10.5, 'precio_unitario' => 30000, 'fecha_vencimiento' => now()->addDays(5)->toDateString()],
                ['producto_id' => $p->idProducto, 'cantidad' => 2.25, 'precio_unitario' => 31000, 'fecha_vencimiento' => now()->addDays(8)->toDateString()],
            ],
        ])->assertRedirect();

        $this->assertEquals(12.75, (float) $p->fresh()->stockActual);
        $this->assertSame(2, Lote::where('Producto_idProducto', $p->idProducto)->count());
        $compra = $prov->compras()->first();
        $this->assertEquals(384750, (float) $compra->total);
        $this->assertEquals(384750, (float) $compra->saldoPendiente);
        $this->assertSame('pendiente', $compra->estadoPago);
    }

    public function test_vencimiento_debe_ser_posterior_al_ingreso(): void
    {
        $p = $this->producto();
        $this->actingAs($this->usuario(Rol::INVENTARIO))->post('/compras', [
            'proveedor_id' => $this->proveedor()->idProveedor,
            'fecha_compra' => now()->format('Y-m-d H:i'),
            'items' => [['producto_id' => $p->idProducto, 'cantidad' => 5, 'precio_unitario' => 1000, 'fecha_vencimiento' => now()->toDateString()]],
        ])->assertSessionHasErrors('items.0.fecha_vencimiento');
        $this->assertEquals(0, (float) $p->fresh()->stockActual);
    }

    public function test_eliminar_producto_lo_marca_inactivo(): void
    {
        $p = $this->producto();
        $this->actingAs($this->usuario(Rol::INVENTARIO))->delete("/productos/{$p->idProducto}")->assertRedirect();
        $this->assertFalse($p->fresh()->activo);
    }

    public function test_alertas_de_vencimiento_y_retiro_ajusta_stock(): void
    {
        $p = $this->producto();
        $this->ingresar($p, 4, now()->addDays(2)->toDateString());
        $this->ingresar($p, 6, now()->addDays(20)->toDateString());

        $servicio = app(AlertaService::class);
        $this->assertSame(1, $servicio->generar(3));
        $this->assertSame(1, $servicio->generar(3)); // no duplica

        $alerta = AlertaVencimiento::first();
        $this->assertSame(2, $alerta->diasRestantes);

        $this->actingAs($this->usuario(Rol::INVENTARIO))
            ->patch("/alertas/{$alerta->idAlerta}/atender", ['accion' => 'retiro', 'detalle' => 'olor'])->assertRedirect();

        $this->assertSame('atendida', $alerta->fresh()->estado);
        $this->assertEquals(6, (float) $p->fresh()->stockActual);
        $this->assertEquals(0, (float) Lote::orderBy('idLote')->first()->cantidadDisponible);
    }

    public function test_sube_reemplaza_y_quita_la_foto_del_producto(): void
    {
        $u = $this->usuario(Rol::INVENTARIO);
        $cat = Categoria::create(['nombre' => 'Cerdo']);
        $datos = ['Categoria_idCategoria' => $cat->idCategoria, 'codigo' => 'FOTO-1', 'nombre' => 'Chuleta', 'unidadVenta' => 'kg', 'precioVenta' => 20000, 'stockMinimo' => 1];

        $this->actingAs($u)->post('/productos', $datos + ['imagen' => UploadedFile::fake()->image('chuleta.png', 1200, 900)])->assertRedirect();
        $p = Producto::where('codigo', 'FOTO-1')->first();
        $this->assertStringStartsWith('uploads/productos/', $p->imagen);
        $this->assertFileExists(public_path($p->imagen));
        [$w, $h] = getimagesize(public_path($p->imagen));
        $this->assertSame([640, 480], [$w, $h]); // recortada a 4:3
        $primera = $p->imagen;

        $this->actingAs($u)->put("/productos/{$p->idProducto}", $datos + ['imagen' => UploadedFile::fake()->image('otra.jpg', 800, 800)])->assertRedirect();
        $this->assertFileDoesNotExist(public_path($primera));
        $segunda = $p->fresh()->imagen;

        $this->actingAs($u)->put("/productos/{$p->idProducto}", $datos + ['quitar_imagen' => 1])->assertRedirect();
        $this->assertNull($p->fresh()->imagen);
        $this->assertFileDoesNotExist(public_path($segunda));
    }

    public function test_rechaza_archivos_que_no_son_imagen(): void
    {
        $cat = Categoria::create(['nombre' => 'Res']);
        $this->actingAs($this->usuario(Rol::INVENTARIO))->post('/productos', [
            'Categoria_idCategoria' => $cat->idCategoria, 'codigo' => 'X1', 'nombre' => 'X', 'unidadVenta' => 'kg', 'precioVenta' => 1, 'stockMinimo' => 0,
            'imagen' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('imagen');
    }

    public function test_sin_foto_propia_usa_la_de_la_categoria(): void
    {
        $cat = Categoria::create(['nombre' => 'Pollo', 'imagen' => 'img/categorias/pollo.jpg']);
        $p = $this->producto(['Categoria_idCategoria' => $cat->idCategoria]);
        $this->assertStringEndsWith('img/categorias/pollo.jpg', $p->fresh()->fotoUrl());
        $this->assertStringEndsWith('img/productos/POL-001.jpg', $this->producto(['imagen' => 'img/productos/POL-001.jpg'])->fotoUrl());
        $sinNada = $this->producto(['Categoria_idCategoria' => Categoria::create(['nombre' => 'Otra'])->idCategoria]);
        $this->assertStringEndsWith('img/sin-foto.svg', $sinNada->fotoUrl());
    }
}
