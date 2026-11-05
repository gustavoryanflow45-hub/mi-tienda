<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;

/**
 * Alias 'api.seller', en todo /api/v1/seller. Las habilidades seller:* se
 * calculan al emitir o rotar el token, así que un token de antes de que el
 * panel rechazara la tienda todavía las tiene: aquí se vuelve a comprobar en
 * cada petición, como hace 'shop.approved' en la web. 403 shop_not_approved.
 */
class EnsureApiSellerShopApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user?->isSeller() || (int) $user->shop?->status !== Shop::APPROVED) {
            return response()->json([
                'message' => __('Tu tienda no está aprobada. No puedes usar el panel de vendedor.'),
                'code' => 'shop_not_approved',
            ], 403);
        }

        return $next($request);
    }
}
