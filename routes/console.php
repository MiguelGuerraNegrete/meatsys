<?php

use App\Services\AlertaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

/*
| Tareas programadas. En el equipo de la carnicería basta con ejecutar
| "php artisan schedule:work" (o una tarea programada de Windows que corra
| "php artisan schedule:run" cada minuto).
*/

Artisan::command('meatsys:alertas {--dias=}', function (AlertaService $servicio) {
    $n = $servicio->generar($this->option('dias') ? (int) $this->option('dias') : null);
    $this->info("Alertas de vencimiento pendientes: {$n}");
})->purpose('Revisa los lotes y genera alertas de productos próximos a vencer (RF6)');

Artisan::command('meatsys:respaldo', function () {
    $dir = storage_path('app/respaldos');
    File::ensureDirectoryExists($dir);
    $fecha = now()->format('Ymd-His');
    $conn = config('database.default');
    $cfg = config("database.connections.$conn");

    if ($cfg['driver'] === 'sqlite') {
        $destino = "$dir/meatsys-$fecha.sqlite";
        File::copy($cfg['database'], $destino);
    } else {
        $destino = "$dir/meatsys-$fecha.sql";
        $bin = env('MYSQLDUMP_PATH', 'mysqldump');
        $cmd = sprintf('"%s" --host=%s --port=%s --user=%s %s --single-transaction --routines %s > "%s"',
            $bin, escapeshellarg($cfg['host']), escapeshellarg((string) $cfg['port']), escapeshellarg($cfg['username']),
            $cfg['password'] !== '' && $cfg['password'] !== null ? '--password='.escapeshellarg($cfg['password']) : '',
            escapeshellarg($cfg['database']), $destino);
        exec($cmd, $out, $codigo);
        if ($codigo !== 0) {
            $this->error('No se pudo ejecutar mysqldump. Configure MYSQLDUMP_PATH en el .env (p. ej. C:\\xampp\\mysql\\bin\\mysqldump.exe).');

            return 1;
        }
    }

    // Conservar solo los últimos 30 respaldos
    collect(File::files($dir))->sortByDesc(fn ($f) => $f->getMTime())->slice(30)->each(fn ($f) => File::delete($f->getPathname()));
    $this->info("Respaldo creado: $destino");
})->purpose('Copia de seguridad diaria de la base de datos');

Schedule::command('meatsys:alertas')->dailyAt('06:00');
Schedule::command('meatsys:respaldo')->dailyAt('22:00');
