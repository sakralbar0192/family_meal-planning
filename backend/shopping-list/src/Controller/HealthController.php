<?php

namespace App\Controller;

use App\Shopping\Database;
use App\Shopping\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    public function __construct(private readonly Database $database)
    {
    }

    #[Route('/api/shopping/v1/health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->database->pdo()->query('SELECT 1');
        } catch (\Throwable) {
            return ErrorResponseFactory::create('DEPENDENCY_DOWN', 'Dependency unavailable.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse([
            'status' => 'ok',
            'checks' => ['postgres' => 'up'],
        ]);
    }
}
