<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = Session::get('locale', config('app.locale'));
        
        // Vérifier si l'utilisateur a une préférence de langue
        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
