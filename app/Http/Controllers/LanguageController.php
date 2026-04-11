<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        if (!in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        Session::put('locale', $locale);

        // Sauvegarder la préférence utilisateur si connecté
        if (Auth::check()) {
            Auth::user()->update(['locale' => $locale]);
        }

        return redirect()->back();
    }
}
