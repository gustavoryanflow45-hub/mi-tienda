<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function change(Request $request)
    {
        $allowed  = ['USD', 'EUR', 'GBP'];
        $currency = in_array($request->currency, $allowed) ? $request->currency : 'USD';
        session(['currency' => $currency]);
        return back();
    }
}
