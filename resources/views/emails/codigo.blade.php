<div style="font-family:Segoe UI,Arial,sans-serif;max-width:480px;margin:auto;color:#2b2522">
    <h2 style="color:#8f1d2c">MeatSys</h2>
    <p>Hola {{ $usuario->nombres }},</p>
    <p>Su código para restablecer la contraseña es:</p>
    <p style="font-size:32px;font-weight:bold;letter-spacing:8px;background:#f7e7e9;padding:12px;text-align:center;border-radius:8px">{{ $codigo }}</p>
    <p>El código vence en {{ config('meatsys.reset_code_minutos') }} minutos. Si usted no lo solicitó, ignore este mensaje.</p>
</div>
