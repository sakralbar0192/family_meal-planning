<?php

namespace App\Controller;

use App\Bff\InternalApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ShoppingController extends BaseProxyController
{
    public function __construct(
        private readonly InternalApiClient $internalApiClient,
        private readonly string $shoppingBaseUri,
    ) {
    }

    #[Route('/bff/v1/shopping/build', name: 'bff_shopping_build', methods: ['POST'])]
    public function build(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->post($request, $this->shoppingBaseUri, '/lists/build', $payload);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/shopping/lists/{listId}', name: 'bff_shopping_get', methods: ['GET'])]
    public function getList(string $listId, Request $request): JsonResponse
    {
        $upstream = $this->internalApiClient->get($request, $this->shoppingBaseUri, '/lists/'.$listId);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/shopping/lists/{listId}/lines', name: 'bff_shopping_add_line', methods: ['POST'])]
    public function addLine(string $listId, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->post($request, $this->shoppingBaseUri, '/lists/'.$listId.'/lines', $payload);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/shopping/lists/{listId}/lines/{lineId}', name: 'bff_shopping_patch_line', methods: ['PATCH'])]
    public function patchLine(string $listId, string $lineId, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->patch(
            $request,
            $this->shoppingBaseUri,
            '/lists/'.$listId.'/lines/'.$lineId,
            $payload
        );

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/shopping/lists/{listId}/lines/{lineId}', name: 'bff_shopping_delete_line', methods: ['DELETE'])]
    public function deleteLine(string $listId, string $lineId, Request $request): JsonResponse
    {
        $upstream = $this->internalApiClient->delete(
            $request,
            $this->shoppingBaseUri,
            '/lists/'.$listId.'/lines/'.$lineId
        );

        return $this->toJsonResponse($upstream);
    }
}
