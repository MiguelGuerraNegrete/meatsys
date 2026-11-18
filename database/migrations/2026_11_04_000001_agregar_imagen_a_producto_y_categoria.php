<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Agrega la foto de productos y categorías. Es la única columna fuera del
 * diccionario de datos original: guarda la ruta relativa a /public.
 * Los productos y categorías que ya existen reciben la foto incluida en
 * public/img si su código (producto) o su nombre (categoría) coincide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto', fn (Blueprint $t) => $t->string('imagen', 255)->nullable()->after('stockMinimo'));
        Schema::table('categoria', fn (Blueprint $t) => $t->string('imagen', 255)->nullable()->after('descripcion'));

        foreach (DB::table('producto')->get(['idProducto', 'codigo']) as $p) {
            $ruta = 'img/productos/'.$p->codigo.'.jpg';
            if (is_file(public_path($ruta))) {
                DB::table('producto')->where('idProducto', $p->idProducto)->update(['imagen' => $ruta]);
            }
        }
        foreach (DB::table('categoria')->get(['idCategoria', 'nombre']) as $c) {
            $ruta = 'img/categorias/'.Str::slug($c->nombre).'.jpg';
            if (is_file(public_path($ruta))) {
                DB::table('categoria')->where('idCategoria', $c->idCategoria)->update(['imagen' => $ruta]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('producto', fn (Blueprint $t) => $t->dropColumn('imagen'));
        Schema::table('categoria', fn (Blueprint $t) => $t->dropColumn('imagen'));
    }
};
