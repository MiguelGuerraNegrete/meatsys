# MeatSys

Sistema de gestión para carnicerías: inventario por lotes, ventas por peso o unidad con factura en PDF, compras y pagos a proveedores, clientes frecuentes y pedidos, alertas de vencimiento, cierre de caja diario y reportes.

Construido con **Laravel 13**, **PHP 8.3+** y **MySQL**, siguiendo la documentación *MeatSys: Sistema de gestión para carnicerías* (Universidad de Córdoba, 2026). Las tablas y columnas son exactamente las del modelo entidad-relación y el diccionario de datos del documento.

---

## 1. Requisitos

- PHP 8.3 o superior con las extensiones `pdo_mysql`, `mbstring`, `gd`, `dom`, `zip` e `intl`
- Composer 2
- MySQL 8 o MariaDB 10.6+

En Windows lo más sencillo es **Laragon** o **XAMPP**, que ya traen PHP y MySQL. No se necesita Node.js: Bootstrap, los íconos y Chart.js vienen incluidos en `public/vendor`, así que la aplicación funciona sin internet en el equipo de la carnicería.

## 2. Instalación

```bash
# 1. Dependencias de PHP
composer install

# 2. Archivo de entorno
copy .env.example .env        # en Linux/Mac: cp .env.example .env
php artisan key:generate
```

3. Cree una base de datos vacía llamada `meatsys` (en phpMyAdmin o con `CREATE DATABASE meatsys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`) y revise los datos de conexión `DB_*` en el `.env`.

4. Cree las tablas y el usuario administrador:

```bash
php artisan migrate --seed
```

5. (Opcional) Cargue datos de demostración: catálogo de 14 productos, 3 proveedores, clientes, compras con lotes, dos semanas de ventas con sus cierres de caja y un pedido pendiente.

```bash
php artisan db:seed --class=DemoSeeder
```

6. Inicie el servidor:

```bash
php artisan serve
```

Abra http://localhost:8000

### Usuarios

| Rol | Usuario | Contraseña | Se crea con |
|---|---|---|---|
| Administrador | `admin` | `Admin12345` | `migrate --seed` |
| Cajero/Vendedor | `cajero` | `Cajero12345` | `DemoSeeder` |
| Encargado de inventario | `inventario` | `Inventario12345` | `DemoSeeder` |

**Cambie la contraseña del administrador antes de usar el sistema en producción.**

## 3. Configuración del negocio (`.env`)

| Variable | Para qué sirve | Valor por defecto |
|---|---|---|
| `MEATSYS_NEGOCIO`, `MEATSYS_NIT`, `MEATSYS_DIRECCION`, `MEATSYS_TELEFONO` | Encabezado de facturas y reportes | — |
| `MEATSYS_IMPUESTO` | % de impuesto sobre las ventas (la carne fresca está excluida de IVA en Colombia) | `0` |
| `MEATSYS_DIAS_ALERTA` | Días de anticipación para alertar vencimientos | `3` |
| `MEATSYS_LOGIN_INTENTOS` / `MEATSYS_LOGIN_BLOQUEO_MIN` | Intentos fallidos antes del bloqueo y minutos de bloqueo | `5` / `15` |
| `MEATSYS_PREFIJO_FACTURA` | Prefijo del consecutivo (FV-000001) | `FV` |
| `MAIL_*` | Servidor de correo para el código de restablecimiento y el envío de facturas. Con `MAIL_MAILER=log` los correos se escriben en `storage/logs/laravel.log` | `log` |
| `MYSQLDUMP_PATH` | Ruta de `mysqldump` para las copias de seguridad. En XAMPP: `C:\xampp\mysql\bin\mysqldump.exe` | `mysqldump` |

## 4. Tareas automáticas

| Comando | Qué hace | Programado |
|---|---|---|
| `php artisan meatsys:alertas` | Revisa los lotes y genera las alertas de vencimiento (RF6) | Todos los días a las 6:00 a. m. |
| `php artisan meatsys:respaldo` | Copia de seguridad de la base de datos en `storage/app/respaldos` (guarda las últimas 30) | Todos los días a las 10:00 p. m. |

Para que corran solas en Windows, cree una tarea en el **Programador de tareas** que ejecute cada minuto:

```
C:\ruta\a\php.exe C:\ruta\a\meatsys\artisan schedule:run
```

O deje abierta una consola con `php artisan schedule:work`. Las alertas también se pueden generar con el botón **Revisar lotes ahora**.

## 5. Cómo se cubre cada requisito

| Req. | Dónde está |
|---|---|
| **RF1** Gestión de productos | `ProductoController`, `CategoriaController`. «Eliminar» marca el producto como inactivo para no romper el historial de ventas. |
| **RF2** Ventas por peso o cantidad | `VentaService::registrar`. Pantalla de venta con búsqueda rápida por nombre, código, corte o categoría (sirve con lector de código de barras), cálculo en vivo, cambio y validación de stock. |
| **RF3** Factura PDF | `FacturaService`. Consecutivo `FV-000001`, formato de tirilla de 80 mm para impresora de recibos, botón Imprimir y envío por correo. |
| **RF4** Compras e ingreso de mercancía | `CompraService::registrar`. Cada producto recibido crea un **lote** con su fecha de vencimiento, que debe ser posterior a la fecha de ingreso. |
| **RF5** Actualización automática del inventario | La compra suma al `stockActual`; la venta descuenta de los lotes que **vencen primero** (y nunca de lotes vencidos), dividiendo la línea en varios `detalle_venta` si toma de más de un lote. |
| **RF6** Vencimientos y alertas | `AlertaService`. Al atender una alerta se registra la acción (promoción, retiro u otra); un **retiro** descuenta el saldo del lote del inventario. |
| **RF7** Proveedores y pagos | `ProveedorController`, `PagoProveedorService`. El pago no puede superar el saldo; el estado pasa a parcial/pagado. Un pago en efectivo se registra como egreso de la caja abierta. |
| **RF8** Clientes y pedidos | `ClienteController` (con historial y autorización de datos, Ley 1581), `PedidoController`. Un pedido se puede **convertir en venta** con «Entregar y facturar». |
| **RF9** Cierre de caja | `CajaService`. Saldo esperado = monto inicial + ingresos − egresos; se compara con el efectivo contado y se genera el PDF del cierre. Solo el administrador cierra. |
| **RF10** Reportes | `ReporteController`: más vendidos, ganancias por periodo, cierres de caja y niveles de inventario, con gráfico y descarga en PDF. La ganancia usa el **costo real del lote** del que salió cada venta. |
| **RF11** Usuarios y roles | `UsuarioController`, middleware `VerificarRol`, inicio de sesión con bloqueo temporal y restablecimiento con código de 6 dígitos al correo. |

**No funcionales:** contraseñas con hash (bcrypt), registro del usuario en cada venta, compra, pago, pedido y caja, pesos con 3 decimales y dinero con 2 (aritmética en milésimas para evitar errores de redondeo, ver `app/Services/Cantidad.php`), operaciones dentro de transacciones con bloqueo de filas para mantener la coherencia entre inventario, ventas y compras, y copias de seguridad diarias.

### Decisiones a tener en cuenta

- **Fotos de productos y categorías:** es la única columna que no está en el diccionario de datos original (`imagen`, texto de 255, opcional, en `producto` y `categoria`). Si el producto no tiene foto propia se muestra la de su categoría. Las fotos subidas se guardan en `public/uploads` (no hace falta `php artisan storage:link`) y se recortan automáticamente a 4:3 si PHP tiene la extensión GD. Las fotos de ejemplo están en `public/img` y vienen de Wikimedia Commons con licencias libres; sus autores aparecen en la pantalla **Créditos de imágenes** y en `public/img/CREDITOS.md`, que deben conservarse si se reutilizan.

- **Caja y medios de pago:** solo las ventas en efectivo entran como ingreso a `movimiento_caja`, porque el cierre compara contra el efectivo contado. Tarjeta y transferencia se muestran aparte en el resumen del cierre.
- **Una caja abierta a la vez.** Para varias sedes habría que agregar una tabla `sede` y relacionarla con `caja`.
- **Proveedores con historial** no se borran físicamente: se desactivan, para no romper la trazabilidad de los lotes.
- **Autorización de datos (Ley 1581):** el formulario de clientes exige la casilla de autorización, pero el modelo de datos no tiene una columna para guardarla. Si necesitan evidencia, conviene agregar `autorizacionDatos` (datetime) a la tabla `cliente`.
- **Balanza electrónica:** el campo de peso acepta lo que escriba un lector o balanza configurada como teclado. Una integración directa por puerto serial quedaría como trabajo futuro.

## Despliegue en Render (gratis)

El proyecto incluye un `Dockerfile` y un `render.yaml` para publicarlo en [Render](https://render.com) con el plan gratuito:

1. Subir el proyecto a un repositorio de GitHub.
2. En Render: **New → Blueprint**, elegir el repositorio y confirmar. Render lee `render.yaml`, construye la imagen y publica la aplicación en `https://<nombre>.onrender.com`.

Cómo se comporta en el plan gratuito:

- **Datos de demostración:** la imagen trae la base de datos con dos semanas de operación de ejemplo, creada al construirla. Como el disco del plan gratuito no es persistente, **cada vez que el servicio se reinicia vuelve a esos datos**. Para empezar con una base vacía, poner `MEATSYS_DEMO=false`.
- **Suspensión:** si nadie la usa durante 15 minutos se suspende, y la siguiente visita tarda cerca de un minuto en despertarla. Antes de una presentación conviene abrirla un par de minutos antes.
- **Correo:** los correos (código de restablecimiento y facturas) se escriben en el registro del servicio (`MAIL_MAILER=log`). Para enviarlos de verdad, configurar las variables `MAIL_*` de un servidor SMTP.

Para un uso real con datos que se conserven, usar un plan con disco persistente o una base de datos MySQL externa (variables `DB_*`) y poner `MEATSYS_DEMO=false`.

## 6. Pruebas

```bash
php artisan test
```

29 pruebas (138 verificaciones) cubren inicio de sesión, bloqueo, restablecimiento de contraseña, permisos por rol, compras y lotes, el descuento por lotes en las ventas, stock insuficiente, lotes vencidos, facturas consecutivas, cierre de caja, pagos a proveedores, alertas, el cálculo de ganancias y la subida de fotos. Usan SQLite en memoria, así que no tocan su base de datos.

## 7. Estructura

```
app/
  Http/Controllers/     Un controlador por módulo
  Http/Middleware/      VerificarRol (acceso por rol)
  Models/               18 modelos, uno por tabla del modelo ER
  Services/             Lógica de negocio: CompraService, VentaService, CajaService,
                        FacturaService, AlertaService, PagoProveedorService, Cantidad
config/meatsys.php      Parámetros del negocio
database/migrations/    Esquema según el diccionario de datos
database/seeders/       DatabaseSeeder (roles + admin) y DemoSeeder
resources/views/        Vistas Blade (Bootstrap 5) y plantillas PDF
routes/web.php          Rutas agrupadas por rol
routes/console.php      Comandos meatsys:alertas y meatsys:respaldo
tests/                  Pruebas unitarias y de funcionalidad
```
