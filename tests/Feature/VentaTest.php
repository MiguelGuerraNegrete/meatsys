<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Factura;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\Rol;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_permite_vender_sin_caja_abierta(): void
    {
        $p = $this->producto();
        $this->ingresar($p, 5, now()->addDays(5)->toDateString());
        $this->actingAs($this->usuario(Rol::CAJERO))->post('/ventas', [
            'metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 1]],
        ])->assertSessionHasErrors('caja');
        $this->assertSame(0, Venta::count());
    }

    public function test_venta_por_peso_descuenta_primero_el_lote_que_vence_antes(): void
    {
        $p = $this->producto(['precioVenta' => 42000]);
        $tardio = $this->ingresar($p, 5, now()->addDays(10)->toDateString())->detalles->first()->lote;
        $temprano = $this->ingresar($p, 1.2, now()->addDays(2)->toDateString())->detalles->first()->lote;
        $this->abrirCaja();

        $this->actingAs($cajero = $this->usuario(Rol::CAJERO))->post('/ventas', [
            'metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 2.253]],
        ])->assertRedirect();

        $venta = Venta::first();
        $this->assertEquals(round(2.253 * 42000, 2), (float) $venta->total);
        $this->assertSame($cajero->idUsuario, $venta->Usuario_idUsuario);

        // se divide en dos detalles: 1.2 kg del lote temprano y 1.053 kg del tardío
        $detalles = DetalleVenta::orderBy('idDetalleVenta')->get();
        $this->assertCount(2, $detalles);
        $this->assertSame($temprano->idLote, $detalles[0]->Lote_idLote);
        $this->assertEquals(1.2, (float) $detalles[0]->cantidad);
        $this->assertEquals(1.053, (float) $detalles[1]->cantidad);

        $this->assertEquals(0, (float) $temprano->fresh()->cantidadDisponible);
        $this->assertEquals(3.947, (float) $tardio->fresh()->cantidadDisponible);
        $this->assertEquals(3.947, (float) $p->fresh()->stockActual);
    }

    public function test_rechaza_cantidad_mayor_al_stock_y_no_modifica_nada(): void
    {
        $p = $this->producto();
        $this->ingresar($p, 1.5, now()->addDays(5)->toDateString());
        $this->abrirCaja();

        $this->actingAs($this->usuario(Rol::CAJERO))->post('/ventas', [
            'metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 2]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, Venta::count());
        $this->assertEquals(1.5, (float) $p->fresh()->stockActual);
    }

    public function test_no_vende_de_lotes_vencidos(): void
    {
        $p = $this->producto();
        $this->ingresar($p, 3, now()->addDay()->toDateString(), 30000, now()->subDays(3)->toDateTimeString());
        $this->travel(2)->days();
        $this->abrirCaja();

        $this->actingAs($this->usuario(Rol::CAJERO))->post('/ventas', [
            'metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 1]],
        ])->assertSessionHasErrors('items');
    }

    public function test_producto_por_unidad_exige_cantidad_entera(): void
    {
        $p = $this->producto(['unidadVenta' => 'unidad']);
        $this->ingresar($p, 10, now()->addDays(5)->toDateString());
        $this->abrirCaja();

        $this->actingAs($this->usuario(Rol::CAJERO))->post('/ventas', [
            'metodo_pago' => 'efectivo', 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 1.5]],
        ])->assertSessionHasErrors('items');
    }

    public function test_genera_factura_pdf_consecutiva_y_registra_ingreso_solo_en_efectivo(): void
    {
        $p = $this->producto(['precioVenta' => 10000]);
        $this->ingresar($p, 10, now()->addDays(5)->toDateString());
        $caja = $this->abrirCaja();
        $cajero = $this->usuario(Rol::CAJERO);

        foreach (['efectivo', 'tarjeta'] as $m) {
            $this->actingAs($cajero)->post('/ventas', ['metodo_pago' => $m, 'items' => [['producto_id' => $p->idProducto, 'cantidad' => 1]]]);
        }

        $this->assertSame(['FV-000001', 'FV-000002'], Factura::orderBy('idFactura')->pluck('numeroFactura')->all());
        Storage::disk('local')->assertExists('facturas/FV-000001.pdf');
        $this->actingAs($cajero)->get('/ventas/1/factura')->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertEquals(10000, (float) $caja->fresh()->totalIngresos);
        $this->assertSame(1, $caja->movimientos()->count());
    }

    public function test_venta_desde_pedido_lo_marca_entregado(): void
    {
        $p = $this->producto();
        $this->ingresar($p, 10, now()->addDays(5)->toDateString());
        $this->abrirCaja();
        $cajero = $this->usuario(Rol::CAJERO);
        $cliente = Cliente::create(['documento' => '123', 'nombre' => 'Cliente', 'fechaRegistro' => now()]);

        $this->actingAs($cajero)->post('/pedidos', [
            'cliente_id' => $cliente->idCliente, 'fechaEntrega' => now()->addDay()->format('Y-m-d H:i'),
            'items' => [['producto_id' => $p->idProducto, 'cantidad' => 2]],
        ])->assertRedirect();
        $pedido = Pedido::first();
        $this->assertSame('pendiente', $pedido->estado);

        $this->actingAs($cajero)->post('/ventas', [
            'metodo_pago' => 'efectivo', 'cliente_id' => $cliente->idCliente, 'pedido_id' => $pedido->idPedido,
            'items' => [['producto_id' => $p->idProducto, 'cantidad' => 2]],
        ])->assertRedirect();
        $this->assertSame('entregado', $pedido->fresh()->estado);
        $this->assertSame($cliente->idCliente, Venta::first()->Cliente_idCliente);
    }
}
