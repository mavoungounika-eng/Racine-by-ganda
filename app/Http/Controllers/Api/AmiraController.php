<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Amira\AmiraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmiraController extends Controller
{
    protected AmiraService $amiraService;

    public function __construct(AmiraService $amiraService)
    {
        $this->amiraService = $amiraService;
    }

    /**
     * Poser une question à Amira
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function ask(Request $request): JsonResponse
    {
        if (!$this->amiraService->isEnabled()) {
            return response()->json([
                'error' => 'Amira is currently disabled.',
            ], 503);
        }

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'context' => 'nullable|array',
        ]);

        $response = $this->amiraService->ask(
            $validated['question'],
            $validated['context'] ?? []
        );

        return response()->json($response);
    }
}
