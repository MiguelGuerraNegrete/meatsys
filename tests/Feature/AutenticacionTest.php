<?php

namespace Tests\Feature;

use App\Mail\CodigoRestablecimiento;
use App\Models\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inicia_sesion_con_usuario_y_contrasena(): void
    {
        $u = $this->usuario(Rol::CAJERO);
        $this->post('/login', ['usuario' => $u->usuario, 'contrasena' => 'Clave12345'])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
    }

    public function test_contrasena_se_guarda_cifrada(): void
    {
        $u = $this->usuario();
        $this->assertNotSame('Clave12345', $u->getRawOriginal('contrasena'));
        $this->assertTrue(Hash::check('Clave12345', $u->getRawOriginal('contrasena')));
    }

    public function test_credenciales_incorrectas_muestran_error(): void
    {
        $u = $this->usuario();
        $this->post('/login', ['usuario' => $u->usuario, 'contrasena' => 'malas'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_bloquea_temporalmente_tras_varios_intentos_fallidos(): void
    {
        $u = $this->usuario();
        for ($i = 0; $i < config('meatsys.login_max_intentos'); $i++) {
            $this->post('/login', ['usuario' => $u->usuario, 'contrasena' => 'malas']);
        }
        // aunque ahora la contraseña sea correcta, la cuenta sigue bloqueada
        $this->post('/login', ['usuario' => $u->usuario, 'contrasena' => 'Clave12345'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
        $this->assertStringContainsString('bloqueada', session('errors')->first('usuario'));
    }

    public function test_usuario_desactivado_no_puede_ingresar(): void
    {
        $u = $this->usuario(Rol::CAJERO, ['activo' => false]);
        $this->post('/login', ['usuario' => $u->usuario, 'contrasena' => 'Clave12345'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_restablece_contrasena_con_codigo_enviado_al_correo(): void
    {
        Mail::fake();
        $u = $this->usuario();
        $this->post('/olvide-contrasena', ['email' => $u->email])->assertRedirect();

        $codigo = null;
        Mail::assertSent(CodigoRestablecimiento::class, function ($m) use (&$codigo, $u) {
            $codigo = $m->codigo;

            return $m->hasTo($u->email);
        });

        $this->post('/restablecer-contrasena', ['email' => $u->email, 'codigo' => $codigo === '000000' ? '111111' : '000000', 'contrasena' => 'Nueva12345', 'contrasena_confirmation' => 'Nueva12345'])
            ->assertSessionHasErrors('codigo');

        $this->post('/restablecer-contrasena', ['email' => $u->email, 'codigo' => $codigo, 'contrasena' => 'Nueva12345', 'contrasena_confirmation' => 'Nueva12345'])
            ->assertRedirect('/login');
        $this->post('/login', ['usuario' => $u->usuario, 'contrasena' => 'Nueva12345'])->assertRedirect('/');
        $this->assertNull($u->fresh()->resetCode);
    }

    public function test_codigo_expirado_no_sirve(): void
    {
        $u = $this->usuario();
        $u->update(['resetCode' => '123456', 'resetCodeExpiration' => now()->subMinute()]);
        $this->post('/restablecer-contrasena', ['email' => $u->email, 'codigo' => '123456', 'contrasena' => 'Nueva12345', 'contrasena_confirmation' => 'Nueva12345'])
            ->assertSessionHasErrors('codigo');
    }

    public function test_acceso_restringido_por_rol(): void
    {
        $this->actingAs($this->usuario(Rol::CAJERO))->get('/usuarios')->assertForbidden();
        $this->actingAs($this->usuario(Rol::CAJERO))->get('/compras/create')->assertForbidden();
        $this->actingAs($this->usuario(Rol::INVENTARIO))->get('/ventas/create')->assertForbidden();
        $this->actingAs($this->usuario(Rol::INVENTARIO))->get('/compras/create')->assertOk();
        $this->actingAs($this->usuario(Rol::ADMIN))->get('/reportes/ganancias')->assertOk();
    }

    public function test_cierra_sesion(): void
    {
        $this->actingAs($this->usuario())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}
