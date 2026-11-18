<?php

namespace App\Services;

/**
 * Aritmética exacta para pesos (3 decimales) y dinero (2 decimales).
 * Las cantidades se manejan internamente como enteros en milésimas
 * para evitar errores de coma flotante al descontar lotes.
 */
final class Cantidad
{
    public static function aMilesimas(float|string $cantidad): int
    {
        return (int) round(((float) $cantidad) * 1000);
    }

    public static function deMilesimas(int $milesimas): float
    {
        return round($milesimas / 1000, 3);
    }

    public static function subtotal(float|string $cantidad, float|string $precio): float
    {
        // milésimas × centavos → redondeo a centavos
        $milesimas = self::aMilesimas($cantidad);
        $centavos = (int) round(((float) $precio) * 100);

        return round(intdiv($milesimas * $centavos + 500, 1000) / 100, 2);
    }

    public static function dinero(float|string $valor): float
    {
        return round((float) $valor, 2);
    }
}
