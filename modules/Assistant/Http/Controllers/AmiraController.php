<?php

namespace Modules\Assistant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Assistant\Services\AmiraService;

class AmiraController extends Controller
{
    protected AmiraService $amiraService;

    public function __construct(AmiraService $amiraService)
    {
        $this->amiraService = $amiraService;
    }

    /**
     * Renvoie la vue partielle du widget
     */
    public function widget()
    {
        if (!config('assistant.amira.enabled', true)) {
            return '';
        }
        return view('assistant::chat');
    }

    /**
     * Traite un message utilisateur
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'context' => 'nullable|array',
        ]);

        $response = $this->amiraService->chat(
            $request->input('message'),
            $request->input('context', [])
        );

        return response()->json($response);
    }

    /**
     * Efface l'historique de conversation
     */
    public function clearHistory(): JsonResponse
    {
        $this->amiraService->clearHistory();

        return response()->json([
            'status' => 'success',
            'message' => 'Historique effacé',
        ]);
    }

    /**
     * Récupère le statut d'Amira
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'enabled' => config('assistant.amira.enabled', true),
            'name' => config('assistant.amira.name', 'Amira'),
            'version' => config('assistant.amira.version', '2.0.0'),
            'provider' => config('assistant.amira.ai.provider', 'mock'),
        ]);
    }
}
