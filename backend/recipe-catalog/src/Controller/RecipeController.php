<?php

namespace App\Controller;

use App\Catalog\ErrorResponseFactory;
use App\Catalog\RecipeRepository;
use App\Catalog\TrustedUserSubscriber;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class RecipeController
{
    public function __construct(private readonly RecipeRepository $recipes)
    {
    }

    #[Route('/api/catalog/v1/recipes', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        $q = $request->query->get('q');
        $meal = $request->query->get('mealCategory');
        $maxCook = $request->query->get('maxCookTimeMinutes');
        $limit = (int) $request->query->get('limit', 50);
        $offset = (int) $request->query->get('offset', 0);
        $limit = \max(1, \min(100, $limit));
        $offset = \max(0, $offset);

        $result = $this->recipes->list(
            $userId,
            \is_string($q) ? $q : null,
            \is_string($meal) ? $meal : null,
            $maxCook !== null && $maxCook !== '' ? (int) $maxCook : null,
            $limit,
            $offset,
        );

        return new JsonResponse($result);
    }

    #[Route('/api/catalog/v1/recipes', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $recipe = $this->recipes->create($userId, $payload);
        if ($recipe === null) {
            return ErrorResponseFactory::create('VALIDATION_ERROR', 'Invalid recipe payload.', Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($recipe, Response::HTTP_CREATED);
    }

    #[Route('/api/catalog/v1/recipes/{recipeId}', methods: ['GET'], requirements: ['recipeId' => '[a-f0-9\\-]{36}'])]
    public function get(string $recipeId, Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        if (!Uuid::isValid($recipeId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Recipe not found.', Response::HTTP_NOT_FOUND);
        }
        $recipe = $this->recipes->findById($userId, $recipeId);
        if ($recipe === null) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Recipe not found.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($recipe);
    }

    #[Route('/api/catalog/v1/recipes/{recipeId}', methods: ['PATCH'], requirements: ['recipeId' => '[a-f0-9\\-]{36}'])]
    public function patch(string $recipeId, Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        if (!Uuid::isValid($recipeId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Recipe not found.', Response::HTTP_NOT_FOUND);
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        try {
            $recipe = $this->recipes->patch($userId, $recipeId, $payload);
        } catch (\InvalidArgumentException $e) {
            return ErrorResponseFactory::create('VALIDATION_ERROR', $e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
        if ($recipe === null) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Recipe not found.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($recipe);
    }

    #[Route('/api/catalog/v1/recipes/{recipeId}', methods: ['DELETE'], requirements: ['recipeId' => '[a-f0-9\\-]{36}'])]
    public function delete(string $recipeId, Request $request): Response
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        if (!Uuid::isValid($recipeId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Recipe not found.', Response::HTTP_NOT_FOUND);
        }
        if (!$this->recipes->delete($userId, $recipeId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Recipe not found.', Response::HTTP_NOT_FOUND);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
