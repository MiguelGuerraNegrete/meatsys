<?php

if (! function_exists('pesos')) {
    /** Formato de dinero colombiano con 2 decimales: $12.345,50 */
    function pesos($valor): string
    {
        return '$'.number_format((float) $valor, 2, ',', '.');
    }
}

if (! function_exists('cant')) {
    /** Cantidad con 3 decimales para kg y entera para unidades. */
    function cant($valor, ?string $unidad = null): string
    {
        $dec = $unidad === 'unidad' ? 0 : 3;
        $txt = number_format((float) $valor, $dec, ',', '.');

        return $unidad ? $txt.' '.($unidad === 'kg' ? 'kg' : 'und') : $txt;
    }
}
