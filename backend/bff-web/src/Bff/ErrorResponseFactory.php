<?php

namespace App\Bff;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ErrorResponseFactory
{
    /**
     * @param array<string, mixed>|null $details
     */
    public static function create(string $code, string $message, int $status, ?array $details = null): JsonResponse
    {
        $payload = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        return new JsonResponse($payload, $status);
    }
}
