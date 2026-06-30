<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        // Only apply to non-idempotent-safe methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $key = $request->header('X-Idempotency-Key') ?: $request->input('idempotency_key');

        if (empty($key)) {
            return response()->json(['error' => 'Missing X-Idempotency-Key header'], 400);
        }

        try {
            // Try to claim the key
            IdempotencyKey::create([
                'key' => $key,
                'status' => 'processing',
            ]);
        } catch (QueryException $e) {
            // duplicate key -> someone else processed or is processing
            $existing = IdempotencyKey::where('key', $key)->first();

            if ($existing->status === 'processing') {
                return response()->json(['error' => 'Request is already being processed'], 409);
            }

            if ($existing->status === 'completed' && $existing->response) {
                $payload = json_decode($existing->response, true);
                return response()->json($payload, 200);
            }

            // Fallback
            return response()->json(['error' => 'Duplicate request'], 409);
        }

        // Proceed and record response into idempotency table
        $response = $next($request);

        try {
            $record = IdempotencyKey::where('key', $key)->first();

            $content = null;
            if ($response instanceof Response) {
                $content = $response->getContent();
            } else {
                $content = (string)$response;
            }

            $record->update([
                'status' => 'completed',
                'response' => $content,
            ]);
        } catch (\Throwable $ex) {
            Log::error('Failed to persist idempotency response: '.$ex->getMessage());
        }

        return $response;
    }
}
