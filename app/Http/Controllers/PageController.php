<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function terms()
    {
        return view('pages.policy', ['title' => __('Términos y condiciones'), 'type' => 'terms']);
    }

    public function returnPolicy()
    {
        return view('pages.policy', ['title' => __('Política de devoluciones'), 'type' => 'return-policy']);
    }

    public function supportPolicy()
    {
        return view('pages.policy', ['title' => __('Política de soporte'), 'type' => 'support-policy']);
    }

    public function privacyPolicy()
    {
        return view('pages.policy', ['title' => __('Política de privacidad'), 'type' => 'privacy-policy']);
    }

    public function trackOrder(Request $request)
    {
        $order = null;
        if ($request->filled('code')) {
            $order = Order::where('code', $request->code)
                ->when(auth()->id(), fn($q) => $q->where('user_id', auth()->id()))
                ->first();
        }
        return view('pages.track-order', compact('order'));
    }

    public function affiliate()
    {
        return view('pages.affiliate');
    }
}
