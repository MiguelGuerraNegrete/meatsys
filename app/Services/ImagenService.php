<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Guarda las fotos subidas en public/uploads/{carpeta} (no requiere
 * "php artisan storage:link", que en Windows pide permisos de administrador).
 * Si la extensión GD está disponible, recorta y reduce la foto para que
 * las pantallas carguen rápido.
 */
class ImagenService
{
    public function guardar(UploadedFile $archivo, string $carpeta, int $ancho = 640, int $alto = 480): string
    {
        $dir = public_path('uploads/'.$carpeta);
        File::ensureDirectoryExists($dir);
        $base = Str::lower(Str::random(16));

        if (function_exists('imagecreatefromstring') && ($origen = @imagecreatefromstring(file_get_contents($archivo->getRealPath())))) {
            $w = imagesx($origen);
            $h = imagesy($origen);
            // recorte centrado con la proporción destino
            $escala = max($ancho / $w, $alto / $h);
            $cw = (int) round($ancho / $escala);
            $ch = (int) round($alto / $escala);
            $destino = imagecreatetruecolor($ancho, $alto);
            imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
            imagecopyresampled($destino, $origen, 0, 0, (int) (($w - $cw) / 2), (int) (($h - $ch) / 2), $ancho, $alto, $cw, $ch);
            imagejpeg($destino, "$dir/$base.jpg", 82);
            imagedestroy($origen);
            imagedestroy($destino);

            return "uploads/$carpeta/$base.jpg";
        }

        $nombre = $base.'.'.$archivo->guessExtension();
        $archivo->move($dir, $nombre);

        return "uploads/$carpeta/$nombre";
    }

    /** Borra una foto subida (las fotos incluidas en public/img no se tocan). */
    public function eliminar(?string $ruta): void
    {
        if ($ruta && str_starts_with($ruta, 'uploads/') && is_file(public_path($ruta))) {
            File::delete(public_path($ruta));
        }
    }
}
