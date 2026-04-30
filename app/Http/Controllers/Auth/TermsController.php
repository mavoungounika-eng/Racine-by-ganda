<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TermsController extends Controller
{
    public function show()
    {
        if (!Auth::check() || Auth::user()->terms_accepted_at) {
            return redirect()->route('frontend.home');
        }

        return view('auth.terms-accept');
    }

    public function accept(Request $request)
    {
        $request->validate([
            'terms' => ['required', 'accepted'],
        ]);

        $user = Auth::user();
        $user->terms_accepted_at = now();
        $user->save();

        return redirect()->intended(route('account.dashboard'));
    }
}
