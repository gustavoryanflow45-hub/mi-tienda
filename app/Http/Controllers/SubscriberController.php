<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        Subscriber::firstOrCreate(['email' => $request->email]);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => __('¡Gracias por suscribirte!')]);
        }

        return back()->with('success', __('¡Gracias por suscribirte a nuestro boletín!'));
    }
}
