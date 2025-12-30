<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class AppearanceController extends Controller
{
    /**
     * Afficher la page des réglages d'apparence
     */
    public function index(): View
    {
        $settings = UserSetting::forUser(Auth::id());
        
        return view('appearance.settings', compact('settings'));
    }

    /**
     * Mettre à jour les préférences d'apparence
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'display_mode' => 'nullable|in:light,dark,auto',
            'accent_palette' => 'nullable|in:orange,yellow,gold,red',
            'animation_intensity' => 'nullable|in:none,soft,standard,luxury',
            'visual_style' => 'nullable|in:female,male,neutral',
            'contrast_level' => 'nullable|in:normal,bright,dark',
            'golden_light_filter' => 'nullable|boolean',
        ]);

        $settings = UserSetting::forUser(Auth::id());
        $settings->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Préférences mises à jour avec succès',
                'settings' => $settings,
                'classes' => $settings->getAllClasses(),
            ]);
        }

        return redirect()->back()->with('success', 'Vos préférences d\'apparence ont été enregistrées.');
    }

    /**
     * Mettre à jour une seule option (pour les toggles rapides)
     */
    public function updateSingle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|in:display_mode,accent_palette,animation_intensity,visual_style,contrast_level,golden_light_filter',
            'value' => 'required',
        ]);

        $settings = UserSetting::forUser(Auth::id());
        $settings->update([$validated['key'] => $validated['value']]);

        return response()->json([
            'success' => true,
            'message' => 'Préférence mise à jour',
            'settings' => $settings,
            'classes' => $settings->getAllClasses(),
        ]);
    }

    /**
     * Réinitialiser aux valeurs par défaut
     */
    public function reset(): RedirectResponse
    {
        $settings = UserSetting::forUser(Auth::id());
        $settings->update(UserSetting::defaults());

        return redirect()->back()->with('success', 'Vos préférences ont été réinitialisées.');
    }

    /**
     * Obtenir les paramètres actuels (API)
     */
    public function current(): JsonResponse
    {
        $settings = UserSetting::forUser(Auth::id());

        return response()->json([
            'settings' => $settings,
            'classes' => $settings->getAllClasses(),
            'theme_class' => $settings->getThemeClass(),
            'accent_color' => $settings->getAccentColor(),
        ]);
    }

    /**
     * Prévisualiser un thème sans sauvegarder
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_mode' => 'required|in:light,dark,auto',
            'accent_palette' => 'nullable|in:orange,yellow,gold,red',
        ]);

        // Créer une instance temporaire pour la prévisualisation
        $tempSettings = new UserSetting($validated);

        return response()->json([
            'theme_class' => $tempSettings->getThemeClass(),
            'accent_color' => $tempSettings->getAccentColor(),
            'classes' => $tempSettings->getAllClasses(),
        ]);
    }
}
