<?php

namespace Tests\Unit;

use App\Services\Cantidad;
use PHPUnit\Framework\TestCase;

class CantidadTest extends TestCase
{
    public function test_subtotal_por_peso_redondea_a_dos_decimales(): void
    {
        $this->assertSame(42000.0, Cantidad::subtotal(1, 42000));
        $this->assertSame(10626.0, Cantidad::subtotal(0.253, 42000));
        $this->assertSame(1.17, Cantidad::subtotal(0.333, 3.5)); // 1.1655 → 1.17
        $this->assertSame(0.0, Cantidad::subtotal(0, 99999));
    }

    public function test_milesimas_sin_errores_de_coma_flotante(): void
    {
        $this->assertSame(1100, Cantidad::aMilesimas(0.1 + 1.0));
        $this->assertSame(300, Cantidad::aMilesimas(0.1 + 0.2));
        $this->assertSame(1.234, Cantidad::deMilesimas(1234));
        // restar 0.3 kg de 1 kg diez veces no deja residuos
        $m = Cantidad::aMilesimas(3);
        for ($i = 0; $i < 10; $i++) {
            $m -= Cantidad::aMilesimas(0.3);
        }
        $this->assertSame(0, $m);
    }
}
