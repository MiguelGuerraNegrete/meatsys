<?php

return [
    // Datos del negocio que aparecen en facturas y reportes
    'negocio' => [
        'nombre' => env('MEATSYS_NEGOCIO', 'Carnicería MeatSys'),
        'nit' => env('MEATSYS_NIT', '900.000.000-0'),
        'direccion' => env('MEATSYS_DIRECCION', 'Montería, Córdoba'),
        'telefono' => env('MEATSYS_TELEFONO', ''),
    ],

    // Porcentaje de impuesto aplicado a las ventas (la carne fresca en Colombia es exenta de IVA, por eso 0)
    'impuesto_porcentaje' => (float) env('MEATSYS_IMPUESTO', 0),

    // Días de anticipación para generar alertas de vencimiento (RF6)
    'dias_alerta_vencimiento' => (int) env('MEATSYS_DIAS_ALERTA', 3),

    // Bloqueo temporal por intentos fallidos de inicio de sesión
    'login_max_intentos' => (int) env('MEATSYS_LOGIN_INTENTOS', 5),
    'login_bloqueo_minutos' => (int) env('MEATSYS_LOGIN_BLOQUEO_MIN', 15),

    // Vigencia del código de restablecimiento de contraseña
    'reset_code_minutos' => 15,

    // Prefijo del consecutivo de factura
    'prefijo_factura' => env('MEATSYS_PREFIJO_FACTURA', 'FV'),
];
