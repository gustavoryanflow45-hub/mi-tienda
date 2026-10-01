<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $products = Product::active()
            ->search($keyword, ['name', 'description'])
            ->paginate(20)
            ->withQueryString();

        $categories = Category::active()->whereNull('parent_id')->get();

        return view('search', compact('products', 'keyword', 'categories'));
    }

    /**
     * Sugerencias del buscador de la cabecera, ya renderizadas.
     *
     * El JS del layout manda el texto como `search` y pinta la respuesta con
     * .html(); '0' significa "sin resultados". Antes leía `keyword` (siempre
     * vacío: devolvía todos los productos) y respondía JSON, que .html() no
     * sabe pintar: el cuadro de sugerencias salía en blanco.
     */
    public function ajax(Request $request)
    {
        $keyword = trim((string) $request->input('search', $request->input('keyword', '')));

        if ($keyword === '') {
            return response('0');
        }

        $products = Product::active()->search($keyword)->take(8)->get();

        if ($products->isEmpty()) {
            return response('0');
        }

        return view('partials.search-suggestions', compact('products', 'keyword'));
    }
}
