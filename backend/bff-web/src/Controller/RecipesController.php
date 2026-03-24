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

    #[Route('/bff/v1/recipes', name: 'bff_list_recipes', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $allowed = ['q', 'mealCategory', 'maxCookTimeMinutes', 'limit', 'offset'];
        $query = [];
        foreach ($allowed as $key) {
            if ($request->query->has($key)) {
                $query[$key] = $request->query->get($key);
            }
        }

        $upstream = $this->internalApiClient->get($request, $this->catalogBaseUri, '/recipes', $query);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/recipes', name: 'bff_create_recipe', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->post($request, $this->catalogBaseUri, '/recipes', $payload);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/recipes/{recipeId}', name: 'bff_get_recipe', methods: ['GET'])]
    public function getOne(string $recipeId, Request $request): JsonResponse
    {
        $upstream = $this->internalApiClient->get($request, $this->catalogBaseUri, '/recipes/'.$recipeId);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/recipes/{recipeId}', name: 'bff_patch_recipe', methods: ['PATCH'])]
    public function patch(string $recipeId, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->patch($request, $this->catalogBaseUri, '/recipes/'.$recipeId, $payload);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/recipes/{recipeId}', name: 'bff_delete_recipe', methods: ['DELETE'])]
    public function delete(string $recipeId, Request $request): JsonResponse
    {
        $upstream = $this->internalApiClient->delete($request, $this->catalogBaseUri, '/recipes/'.$recipeId);

        return $this->toJsonResponse($upstream);
    }
}
