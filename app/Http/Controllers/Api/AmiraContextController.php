<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Amira\AmiraContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AmiraContextController extends Controller
{
    public function __construct(private AmiraContextService $contextService) {}

    public function chat(Request $request): JsonResponse
    {
        if (! config('amira.enabled', true)) {
            return response()->json(['error' => 'Amira est désactivée.'], 503);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'space'   => 'required|in:client,creator,admin',
        ]);

        $user  = Auth::user();
        $space = $validated['space'];

        // Basic RBAC guard: admin space only for admin/super_admin
        if ($space === 'admin' && ! in_array($user->role, ['admin', 'super_admin'])) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $openAiKey = config('openai.api_key') ?: config('amira.nlp_api_key');
        $isPlaceholder = empty($openAiKey)
            || str_starts_with($openAiKey, 'REPLACE_')
            || $openAiKey === 'your-api-key';

        if ($isPlaceholder) {
            return response()->json([
                'answer' => "Je suis Amira ! Pour m'activer, configure OPENAI_API_KEY dans ton .env.",
                'space'  => $space,
                'disabled' => true,
            ]);
        }

        try {
            $context      = $this->contextService->buildContext($user, $space);
            $systemPrompt = $this->contextService->buildSystemPrompt($space);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'system', 'content' => 'Contexte utilisateur : ' . json_encode($context, JSON_UNESCAPED_UNICODE)],
                ['role' => 'user', 'content' => $validated['message']],
            ];

            $result = OpenAI::chat()->create([
                'model'       => config('amira.model', 'gpt-4o-mini'),
                'messages'    => $messages,
                'max_tokens'  => config('amira.max_tokens', 250),
                'temperature' => config('amira.temperature', 0.65),
            ]);

            $answer = trim($result->choices[0]->message->content);

            return response()->json(['answer' => $answer, 'space' => $space]);
        } catch (\Exception $e) {
            Log::warning('Amira chat error', ['message' => $e->getMessage(), 'space' => $space]);

            return response()->json([
                'answer' => "Désolée, je rencontre une difficulté. Réessaie dans un instant !",
                'space'  => $space,
                'error'  => true,
            ]);
        }
    }
}
