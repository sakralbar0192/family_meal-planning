<?php

namespace App\Controller;

use App\Bff\InternalApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RecipesController extends BaseProxyController
{
    public function __construct(
        private readonly InternalApiClient $internalApiClient,
        private readonly string $catalogBaseUri,
    ) {
    }

    #[Route('/bff/v1/recipes', name: 'bff_create_recipe', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->post($request, $this->catalogBaseUri, '/recipes', $payload);

        return $this->toJsonResponse($upstream);
    }
}
