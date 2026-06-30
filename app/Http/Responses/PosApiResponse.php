<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PosApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = '',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => [
                'request_id' => Str::uuid()->toString(),
                'timestamp' => now()->toIso8601String(),
                'message' => $message ?: null,
            ],
        ], $status);
    }

    public static function error(
        string $code,
        string $message,
        mixed $details = null,
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'request_id' => Str::uuid()->toString(),
                'timestamp' => now()->toIso8601String(),
            ],
        ], $status);
    }

    public static function notFound(string $resource = 'Resource'): JsonResponse
    {
        return self::error(
            'NOT_FOUND',
            "{$resource} not found.",
            null,
            404
        );
    }

    public static function unauthorized(string $message = 'Unauthorized.'): JsonResponse
    {
        return self::error('UNAUTHORIZED', $message, null, 401);
    }

    public static function validationError(array $errors): JsonResponse
    {
        return self::error(
            'VALIDATION_ERROR',
            'The given data was invalid.',
            $errors,
            422
        );
    }
}
