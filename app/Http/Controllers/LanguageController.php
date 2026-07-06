<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function change(Request $request)
    {
        $allowed = ['es', 'en'];
        $lang    = in_array($request->lang, $allowed) ? $request->lang : 'es';
        session(['locale' => $lang]);
        return back();
    }
}
