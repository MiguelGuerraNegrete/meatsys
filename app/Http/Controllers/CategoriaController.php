<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Services\ImagenService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index()
    {
        return view('categorias.index', ['categorias' => Categoria::withCount(['productos' => fn ($q) => $q->where('activo', true)])->orderBy('nombre')->get()]);
    }

    public function store(Request $request, ImagenService $imagenes)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'unique:categoria,nombre'],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $imagenes->guardar($request->file('imagen'), 'categorias', 800, 500);
        }
        Categoria::create($datos);

        return back()->with('ok', 'Categoría creada.');
    }

    public function update(Request $request, Categoria $categoria, ImagenService $imagenes)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:50', Rule::unique('categoria', 'nombre')->ignore($categoria->idCategoria, 'idCategoria')],
            'descripcion' => ['nullable', 'string', 'max:200'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($request->hasFile('imagen')) {
            $imagenes->eliminar($categoria->imagen);
            $datos['imagen'] = $imagenes->guardar($request->file('imagen'), 'categorias', 800, 500);
        }
        $categoria->update($datos);

        return back()->with('ok', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return back()->withErrors(['categoria' => 'No se puede eliminar: la categoría tiene productos asociados.']);
        }
        $categoria->delete();

        return back()->with('ok', 'Categoría eliminada.');
    }
}
