<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaYPagosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cierre_de_caja_calcula_saldo_y_diferencia(): void
    {
        $p = $this->producto(['precioVenta' => 20000]);
        $this->ingresar($p, 10, now()->addDays(5)->toDateString());
        $cajero = $this->usuario(Rol::CAJERO);
        $admin = $this->usuario(Rol::ADMIN);

        $this->actingAs($cajero)->post('/caja/abrir', ['montoInicial' => 50000])->assertRedirect();
        $this->actingAs($cajero)->post('/caja/abrir', ['montoInicial' => 1])->assertSessionHasErrors('montoInicial');
        $this->actingAs($cajero)->post('/ventas', ['metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 1.5]]]);
        $this->actingAs($cajero)->post('/caja/movimiento', ['tipo' => 'egreso', 'concepto' => 'Bolsas', 'monto' => 5000]);

        // el cajero no puede cerrar la caja
        $this->actingAs($cajero)->post('/caja/cierre', ['montoContado' => 75000])->assertForbidden();

        $this->actingAs($admin)->post('/caja/cierre', ['montoContado' => 74000, 'observaciones' => 'Faltan 1000'])->assertRedirect();
        $caja = Caja::first();
        $this->assertSame('cerrada', $caja->estado);
        $this->assertEquals(30000, (float) $caja->totalIngresos);
        $this->assertEquals(5000, (float) $caja->totalEgresos);
        $this->assertEquals(75000, (float) $caja->saldoFinal);
        $this->assertEquals(74000, (float) $caja->montoContado);
        $this->actingAs($admin)->get("/caja/{$caja->idCaja}/pdf")->assertOk();
    }

    public function test_pago_a_proveedor_no_supera_saldo_y_actualiza_estado(): void
    {
        $compra = $this->ingresar($this->producto(), 10, now()->addDays(5)->toDateString(), 10000); // total 100.000
        $admin = $this->usuario(Rol::ADMIN);
        $this->abrirCaja(200000);

        $this->actingAs($admin)->post("/compras/{$compra->idCompra}/pagar", ['monto' => 100000.01, 'metodoPago' => 'efectivo', 'fechaPago' => now()->format('Y-m-d H:i')])
            ->assertSessionHasErrors('monto');

        $this->actingAs($admin)->post("/compras/{$compra->idCompra}/pagar", ['monto' => 40000, 'metodoPago' => 'efectivo', 'fechaPago' => now()->format('Y-m-d H:i')]);
        $this->assertSame('parcial', $compra->fresh()->estadoPago);
        $this->assertEquals(60000, (float) $compra->fresh()->saldoPendiente);
        $this->assertEquals(40000, (float) Caja::abierta()->totalEgresos);

        $this->actingAs($admin)->post("/compras/{$compra->idCompra}/pagar", ['monto' => 60000, 'metodoPago' => 'transferencia', 'fechaPago' => now()->format('Y-m-d H:i')]);
        $this->assertSame('pagado', $compra->fresh()->estadoPago);
        $this->assertEquals(0, (float) $compra->fresh()->saldoPendiente);
        $this->assertEquals(40000, (float) Caja::abierta()->totalEgresos); // la transferencia no sale de caja
    }

    public function test_no_elimina_proveedor_con_saldo_pendiente(): void
    {
        $compra = $this->ingresar($this->producto(), 1, now()->addDays(5)->toDateString());
        $this->actingAs($this->usuario(Rol::ADMIN))->delete("/proveedores/{$compra->Proveedor_idProveedor}")->assertSessionHasErrors('proveedor');
        $this->assertNotNull($compra->proveedor->fresh());
    }

    public function test_reportes_calculan_ganancia_con_costo_del_lote(): void
    {
        $p = $this->producto(['precioVenta' => 50000]);
        $this->ingresar($p, 10, now()->addDays(5)->toDateString(), 30000);
        $this->abrirCaja();
        $this->actingAs($this->usuario(Rol::CAJERO))->post('/ventas', ['metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 2]]]);

        $r = $this->actingAs($this->usuario(Rol::ADMIN))->get('/reportes/ganancias');
        $r->assertOk();
        $this->assertEquals(100000, $r->viewData('datos')['totales']['ventas']);
        $this->assertEquals(60000, $r->viewData('datos')['totales']['costo']);
        $this->assertEquals(40000, $r->viewData('datos')['totales']['ganancia']);

        $this->actingAs($this->usuario(Rol::ADMIN))->get('/reportes/mas-vendidos?pdf=1')->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
