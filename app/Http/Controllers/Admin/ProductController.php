<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\AuditLog;
use App\Services\ProductService;
use Illuminate\Http\Request;

/**
 * Moderación de productos de todos los vendedores (/admin/products).
 *
 * - Aprobar / desactivar: products.approved. Desactivado no se ve en la
 *   tienda aunque su vendedor lo tenga publicado (published es del vendedor).
 * - Editar: el mismo formulario y el mismo ProductService que el panel del
 *   vendedor; el dueño (added_by) no cambia.
 *
 * Quién puede lo decide ProductPolicy (update, moderate).
 */
class ProductController extends Controller
{
    /** Filtro de estado del listado. */
    public const STATUSES = ['active', 'disabled', 'unpublished'];

    public function __construct(private ProductService $products)
    {
        // Las rutas ya llevan 'auth:admin' + 'admin'; defensa en profundidad.
        $this->middleware(['auth:admin', 'admin']);
    }

    // ── GET /admin/products ──────────────────────────────────────
    public function index(Request $request)
    {
        $query = Product::with(['category', 'seller.shop'])->withCount('stocks');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('seller')) {
            $query->where('added_by', (int) $request->seller);
        }

        match ($request->get('status')) {
            'active' => $query->active(),
            'disabled' => $query->where('approved', 0),
            'unpublished' => $query->where('published', 0),
            default => null,
        };

        $products = $query->latest()->paginate(20)->withQueryString();

        $counts = [
            'total' => Product::count(),
            'active' => Product::active()->count(),
            'disabled' => Product::where('approved', 0)->count(),
            'unpublished' => Product::where('published', 0)->count(),
        ];

        $shops = Shop::orderBy('name')->get(['user_id', 'name']);

        return view('admin.products.index', compact('products', 'counts', 'shops'));
    }

    // ── GET /admin/products/{id} ─────────────────────────────────
    public function show(int $id)
    {
        $product = Product::with(['category', 'brand', 'stocks', 'seller.shop'])->findOrFail($id);

        return view('admin.products.show', compact('product'));
    }

    // ── GET /admin/products/{id}/edit ────────────────────────────
    public function edit(int $id)
    {
        $product = Product::with('stocks')->findOrFail($id);
        $this->authorize('update', $product);

        $categories = Category::where('status', 1)->orderBy('name')->get();
        $brands = Brand::where('status', 1)->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'brands'));
    }

    // ── PUT /admin/products/{id} ─────────────────────────────────
    public function update(Request $request, int $id, AuditLog $audit)
    {
        $product = Product::with('stocks')->findOrFail($id);
        $this->authorize('update', $product);

        $this->products->validate($request->all(), creating: false);
        $this->products->update($product, $request->all(), $request->file('thumbnail'), $request->file('photos', []));

        $audit->record('product.edited', $product, ['seller_id' => $product->added_by], $request->user('admin'));

        return redirect()->route('admin.products.show', $product->id)
            ->with('success', __('Producto «:name» actualizado.', ['name' => $product->name]));
    }

    // ── POST /admin/products/{id}/approve ────────────────────────
    public function approve(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('moderate', $product);

        if (! $this->products->setApproved($product, true, $request->user('admin'))) {
            return back()->with('warning', __('El producto «:name» ya estaba aprobado.', ['name' => $product->name]));
        }

        return back()->with('success', __('Producto «:name» aprobado: vuelve a verse en la tienda si su vendedor lo tiene publicado.', ['name' => $product->name]));
    }

    // ── POST /admin/products/{id}/disable ────────────────────────
    public function disable(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $this->authorize('moderate', $product);

        if (! $this->products->setApproved($product, false, $request->user('admin'))) {
            return back()->with('warning', __('El producto «:name» ya estaba desactivado.', ['name' => $product->name]));
        }

        return back()->with('success', __('Producto «:name» desactivado: ya no se muestra en la tienda ni se puede añadir al carrito.', ['name' => $product->name]));
    }
}
