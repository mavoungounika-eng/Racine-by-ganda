<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function showClient(): View
    {
        $user = Auth::user();
        if ($user->onboarding_completed) {
            abort(redirect()->route('account.dashboard'));
        }

        return view('onboarding.client', compact('user'));
    }

    public function showCreator(): View
    {
        $user = Auth::user();
        if ($user->onboarding_completed) {
            abort(redirect()->route('creator.dashboard'));
        }

        return view('onboarding.creator', compact('user'));
    }

    public function completeClient(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user->update([
            'onboarding_completed' => true,
            'onboarding_type'      => 'client',
        ]);

        return redirect()->route('account.dashboard')
            ->with('success', 'Bienvenue sur Racine ! Votre compte est prêt.');
    }

    public function completeCreator(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user->update([
            'onboarding_completed' => true,
            'onboarding_type'      => 'creator',
        ]);

        return redirect()->route('creator.dashboard')
            ->with('success', 'Votre boutique est prête. Bonne vente !');
    }

    public function skip(): RedirectResponse
    {
        $user = Auth::user();
        $user->update(['onboarding_completed' => true]);

        $dest = in_array($user->role, ['createur', 'creator'])
            ? 'creator.dashboard'
            : 'account.dashboard';

        return redirect()->route($dest);
    }
}
