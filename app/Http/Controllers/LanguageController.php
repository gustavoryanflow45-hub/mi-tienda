<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function change(Request $request)
    {
        // El topbar envía el parámetro como "locale"; se acepta "lang"
        // como alias por si alguna vista vieja aún lo usa.
        $requested = $request->input('locale', $request->input('lang'));

        $lang = in_array($requested, SetLocale::SUPPORTED, true)
            ? $requested
            : config('app.locale');

        session(['locale' => $lang]);

        return back();
    }
}
