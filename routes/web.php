<?php

use App\Http\Controllers\AlertaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RestablecerController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PagoProveedorController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

/*
| Roles: administrador (acceso total), cajero (ventas, clientes, pedidos),
| inventario (productos, compras, lotes, alertas, niveles de inventario).
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:30,1');
    Route::get('/olvide-contrasena', [RestablecerController::class, 'solicitar'])->name('password.request');
    Route::post('/olvide-contrasena', [RestablecerController::class, 'enviar'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-contrasena', [RestablecerController::class, 'formulario'])->name('password.reset');
    Route::post('/restablecer-contrasena', [RestablecerController::class, 'restablecer'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware(['auth', 'rol'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/creditos', fn () => view('creditos.index', [
        'creditos' => json_decode((string) @file_get_contents(public_path('img/creditos.json')), true) ?: [],
    ]))->name('creditos');

    // Consulta de productos: todos los roles
    Route::get('/productos', [ProductoController::class, 'index'])->name('productos.index');
    Route::get('/api/productos', [ProductoController::class, 'buscar'])->name('productos.buscar');

    // ---------- Administrador ----------
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'toggle'])->name('usuarios.toggle');

        Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor']);

        Route::get('pagos', [PagoProveedorController::class, 'index'])->name('pagos.index');
        Route::get('compras/{compra}/pagar', [PagoProveedorController::class, 'create'])->name('pagos.create');
        Route::post('compras/{compra}/pagar', [PagoProveedorController::class, 'store'])->name('pagos.store');

        Route::get('caja/cierre', [CajaController::class, 'cierre'])->name('caja.cierre');
        Route::post('caja/cierre', [CajaController::class, 'cerrar'])->name('caja.cerrar');
        Route::get('caja/{caja}', [CajaController::class, 'show'])->whereNumber('caja')->name('caja.show');
        Route::get('caja/{caja}/pdf', [CajaController::class, 'pdf'])->name('caja.pdf');

        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/{tipo}', [ReporteController::class, 'show'])->name('reportes.show');
    });

    // ---------- Administrador + Encargado de inventario ----------
    Route::middleware('rol:administrador,inventario')->group(function () {
        Route::get('productos/crear', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('productos/{producto}/editar', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy'])->name('productos.destroy');
        Route::patch('productos/{producto}/activar', [ProductoController::class, 'activar'])->name('productos.activar');

        Route::resource('categorias', CategoriaController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('compras', CompraController::class)->only(['index', 'create', 'store', 'show']);

        Route::get('inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('inventario/{producto}/lotes', [InventarioController::class, 'lotes'])->name('inventario.lotes');

        Route::get('alertas', [AlertaController::class, 'index'])->name('alertas.index');
        Route::post('alertas/generar', [AlertaController::class, 'generar'])->name('alertas.generar');
        Route::patch('alertas/{alerta}/atender', [AlertaController::class, 'atender'])->name('alertas.atender');
    });

    // ---------- Administrador + Cajero ----------
    Route::middleware('rol:administrador,cajero')->group(function () {
        Route::get('caja', [CajaController::class, 'index'])->name('caja.index');
        Route::post('caja/abrir', [CajaController::class, 'abrir'])->name('caja.abrir');
        Route::post('caja/movimiento', [CajaController::class, 'movimiento'])->name('caja.movimiento');

        Route::resource('ventas', VentaController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('ventas/{venta}/factura', [VentaController::class, 'factura'])->name('ventas.factura');
        Route::post('ventas/{venta}/enviar', [VentaController::class, 'enviar'])->name('ventas.enviar');

        Route::get('api/clientes', [ClienteController::class, 'buscar'])->name('clientes.buscar');
        Route::resource('clientes', ClienteController::class)->except(['destroy']);

        Route::resource('pedidos', PedidoController::class)->only(['index', 'create', 'store', 'show']);
        Route::patch('pedidos/{pedido}/estado', [PedidoController::class, 'estado'])->name('pedidos.estado');
    });
});
