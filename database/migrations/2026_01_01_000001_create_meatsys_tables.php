<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema de MeatSys según el modelo entidad-relación y el diccionario de datos
 * (sección 2.7 y 2.9 de la documentación). Nombres de tablas y columnas
 * se conservan tal como aparecen en el documento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol', function (Blueprint $t) {
            $t->increments('idRol');
            $t->string('nombre', 30)->unique();
        });

        Schema::create('usuario', function (Blueprint $t) {
            $t->increments('idUsuario');
            $t->unsignedInteger('Rol_idRol');
            $t->string('documento', 15)->unique();
            $t->string('nombres', 60);
            $t->string('apellidos', 60);
            $t->string('email', 80)->unique();
            $t->string('usuario', 30)->unique();
            $t->string('contrasena', 200);
            $t->string('telefono', 15)->nullable();
            $t->boolean('activo')->default(true);
            $t->string('resetCode', 6)->nullable();
            $t->dateTime('resetCodeExpiration')->nullable();
            $t->date('fechaCreacion');
            $t->foreign('Rol_idRol')->references('idRol')->on('rol');
        });

        Schema::create('categoria', function (Blueprint $t) {
            $t->increments('idCategoria');
            $t->string('nombre', 50)->unique();
            $t->string('descripcion', 200)->nullable();
        });

        Schema::create('producto', function (Blueprint $t) {
            $t->increments('idProducto');
            $t->unsignedInteger('Categoria_idCategoria');
            $t->string('codigo', 20)->unique();
            $t->string('nombre', 100);
            $t->string('tipoCorte', 60)->nullable();
            $t->enum('unidadVenta', ['kg', 'unidad']);
            $t->decimal('precioVenta', 10, 2);
            $t->decimal('stockActual', 10, 3)->default(0);
            $t->decimal('stockMinimo', 10, 3)->default(0);
            $t->boolean('activo')->default(true);
            $t->foreign('Categoria_idCategoria')->references('idCategoria')->on('categoria');
        });

        Schema::create('proveedor', function (Blueprint $t) {
            $t->increments('idProveedor');
            $t->string('nit', 20)->unique();
            $t->string('razonSocial', 120);
            $t->string('telefono', 15)->nullable();
            $t->string('email', 80)->nullable();
            $t->string('direccion', 150)->nullable();
            $t->boolean('activo')->default(true);
        });

        Schema::create('compra', function (Blueprint $t) {
            $t->increments('idCompra');
            $t->unsignedInteger('Proveedor_idProveedor');
            $t->unsignedInteger('Usuario_idUsuario');
            $t->string('numeroFactura', 30)->nullable();
            $t->dateTime('fechaCompra');
            $t->decimal('total', 12, 2)->default(0);
            $t->decimal('saldoPendiente', 12, 2)->default(0);
            $t->enum('estadoPago', ['pendiente', 'parcial', 'pagado'])->default('pendiente');
            $t->foreign('Proveedor_idProveedor')->references('idProveedor')->on('proveedor');
            $t->foreign('Usuario_idUsuario')->references('idUsuario')->on('usuario');
        });

        Schema::create('detalle_compra', function (Blueprint $t) {
            $t->increments('idDetalleCompra');
            $t->unsignedInteger('Compra_idCompra');
            $t->unsignedInteger('Producto_idProducto');
            $t->decimal('cantidad', 10, 3);
            $t->decimal('precioUnitario', 10, 2);
            $t->decimal('subtotal', 12, 2);
            $t->foreign('Compra_idCompra')->references('idCompra')->on('compra')->cascadeOnDelete();
            $t->foreign('Producto_idProducto')->references('idProducto')->on('producto');
        });

        Schema::create('pago_proveedor', function (Blueprint $t) {
            $t->increments('idPagoProveedor');
            $t->unsignedInteger('Compra_idCompra');
            $t->unsignedInteger('Usuario_idUsuario');
            $t->dateTime('fechaPago');
            $t->decimal('monto', 12, 2);
            $t->enum('metodoPago', ['efectivo', 'transferencia', 'otro']);
            $t->foreign('Compra_idCompra')->references('idCompra')->on('compra');
            $t->foreign('Usuario_idUsuario')->references('idUsuario')->on('usuario');
        });

        Schema::create('lote', function (Blueprint $t) {
            $t->increments('idLote');
            $t->unsignedInteger('Producto_idProducto');
            $t->unsignedInteger('DetalleCompra_idDetalleCompra');
            $t->decimal('cantidadInicial', 10, 3);
            $t->decimal('cantidadDisponible', 10, 3);
            $t->date('fechaIngreso');
            $t->date('fechaVencimiento');
            $t->foreign('Producto_idProducto')->references('idProducto')->on('producto');
            $t->foreign('DetalleCompra_idDetalleCompra')->references('idDetalleCompra')->on('detalle_compra');
            $t->index(['Producto_idProducto', 'fechaVencimiento']);
        });

        Schema::create('alerta_vencimiento', function (Blueprint $t) {
            $t->increments('idAlerta');
            $t->unsignedInteger('Lote_idLote');
            $t->dateTime('fechaGeneracion');
            $t->integer('diasRestantes');
            $t->enum('estado', ['pendiente', 'atendida'])->default('pendiente');
            $t->string('accionTomada', 200)->nullable();
            $t->foreign('Lote_idLote')->references('idLote')->on('lote');
        });

        Schema::create('cliente', function (Blueprint $t) {
            $t->increments('idCliente');
            $t->string('documento', 15)->unique();
            $t->string('nombre', 120);
            $t->string('telefono', 15)->nullable();
            $t->string('email', 80)->nullable();
            $t->string('direccion', 150)->nullable();
            $t->date('fechaRegistro');
        });

        Schema::create('pedido', function (Blueprint $t) {
            $t->increments('idPedido');
            $t->unsignedInteger('Cliente_idCliente');
            $t->unsignedInteger('Usuario_idUsuario');
            $t->dateTime('fechaPedido');
            $t->dateTime('fechaEntrega');
            $t->enum('estado', ['pendiente', 'listo', 'entregado', 'cancelado'])->default('pendiente');
            $t->string('observaciones', 300)->nullable();
            $t->foreign('Cliente_idCliente')->references('idCliente')->on('cliente');
            $t->foreign('Usuario_idUsuario')->references('idUsuario')->on('usuario');
        });

        Schema::create('detalle_pedido', function (Blueprint $t) {
            $t->increments('idDetallePedido');
            $t->unsignedInteger('Pedido_idPedido');
            $t->unsignedInteger('Producto_idProducto');
            $t->decimal('cantidad', 10, 3);
            $t->foreign('Pedido_idPedido')->references('idPedido')->on('pedido')->cascadeOnDelete();
            $t->foreign('Producto_idProducto')->references('idProducto')->on('producto');
        });

        Schema::create('caja', function (Blueprint $t) {
            $t->increments('idCaja');
            $t->unsignedInteger('Usuario_idUsuario');
            $t->dateTime('fechaApertura');
            $t->dateTime('fechaCierre')->nullable();
            $t->decimal('montoInicial', 12, 2);
            $t->decimal('totalIngresos', 12, 2)->default(0);
            $t->decimal('totalEgresos', 12, 2)->default(0);
            $t->decimal('saldoFinal', 12, 2)->nullable();
            $t->decimal('montoContado', 12, 2)->nullable();
            $t->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $t->string('observaciones', 300)->nullable();
            $t->foreign('Usuario_idUsuario')->references('idUsuario')->on('usuario');
        });

        Schema::create('movimiento_caja', function (Blueprint $t) {
            $t->increments('idMovimiento');
            $t->unsignedInteger('Caja_idCaja');
            $t->enum('tipo', ['ingreso', 'egreso']);
            $t->string('concepto', 150);
            $t->decimal('monto', 12, 2);
            $t->dateTime('fechaHora');
            $t->foreign('Caja_idCaja')->references('idCaja')->on('caja');
        });

        Schema::create('venta', function (Blueprint $t) {
            $t->increments('idVenta');
            $t->unsignedInteger('Cliente_idCliente')->nullable();
            $t->unsignedInteger('Usuario_idUsuario');
            $t->unsignedInteger('Caja_idCaja');
            $t->dateTime('fechaHora');
            $t->decimal('subtotal', 12, 2);
            $t->decimal('impuesto', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->enum('metodoPago', ['efectivo', 'tarjeta', 'transferencia']);
            $t->foreign('Cliente_idCliente')->references('idCliente')->on('cliente');
            $t->foreign('Usuario_idUsuario')->references('idUsuario')->on('usuario');
            $t->foreign('Caja_idCaja')->references('idCaja')->on('caja');
            $t->index('fechaHora');
        });

        Schema::create('detalle_venta', function (Blueprint $t) {
            $t->increments('idDetalleVenta');
            $t->unsignedInteger('Venta_idVenta');
            $t->unsignedInteger('Producto_idProducto');
            $t->unsignedInteger('Lote_idLote');
            $t->decimal('cantidad', 10, 3);
            $t->decimal('precioUnitario', 10, 2);
            $t->decimal('subtotal', 12, 2);
            $t->foreign('Venta_idVenta')->references('idVenta')->on('venta')->cascadeOnDelete();
            $t->foreign('Producto_idProducto')->references('idProducto')->on('producto');
            $t->foreign('Lote_idLote')->references('idLote')->on('lote');
        });

        Schema::create('factura', function (Blueprint $t) {
            $t->increments('idFactura');
            $t->unsignedInteger('Venta_idVenta')->unique();
            $t->string('numeroFactura', 20)->unique();
            $t->dateTime('fechaEmision');
            $t->string('rutaPdf', 255)->nullable();
            $t->foreign('Venta_idVenta')->references('idVenta')->on('venta');
        });
    }

    public function down(): void
    {
        foreach ([
            'factura', 'detalle_venta', 'venta', 'movimiento_caja', 'caja',
            'detalle_pedido', 'pedido', 'cliente', 'alerta_vencimiento', 'lote',
            'pago_proveedor', 'detalle_compra', 'compra', 'proveedor',
            'producto', 'categoria', 'usuario', 'rol',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
