<?php

namespace App\Controller;

use App\Shopping\ErrorResponseFactory;
use App\Shopping\ShoppingListRepository;
use App\Shopping\TrustedUserSubscriber;
use App\Shopping\UpstreamClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class ShoppingListController
{
    public function __construct(
        private readonly ShoppingListRepository $lists,
        private readonly UpstreamClient $upstream,
    ) {
    }

    #[Route('/api/shopping/v1/lists/build', methods: ['POST'])]
    public function build(Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $from = isset($payload['from']) && \is_string($payload['from']) ? $payload['from'] : '';
        $to = isset($payload['to']) && \is_string($payload['to']) ? $payload['to'] : '';
        if ($from === '' || $to === '' || $from > $to) {
            return ErrorResponseFactory::create('VALIDATION_ERROR', 'Invalid from/to dates.', Response::HTTP_BAD_REQUEST);
        }

        $result = $this->lists->build($userId, $from, $to, $this->upstream, $request);

        return new JsonResponse($result);
    }

    #[Route('/api/shopping/v1/lists/{listId}', methods: ['GET'], requirements: ['listId' => '[a-f0-9\\-]{36}'])]
    public function get(string $listId, Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        if (!Uuid::isValid($listId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'List not found.', Response::HTTP_NOT_FOUND);
        }
        $detail = $this->lists->getListDetail($userId, $listId);
        if ($detail === null) {
            return ErrorResponseFactory::create('NOT_FOUND', 'List not found.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($detail);
    }

    #[Route('/api/shopping/v1/lists/{listId}/lines', methods: ['POST'], requirements: ['listId' => '[a-f0-9\\-]{36}'])]
    public function addLine(string $listId, Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        if (!Uuid::isValid($listId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'List not found.', Response::HTTP_NOT_FOUND);
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $line = $this->lists->addManualLine($userId, $listId, $payload);
        if ($line === null) {
            return ErrorResponseFactory::create('NOT_FOUND', 'List not found.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($line, Response::HTTP_CREATED);
    }

    #[Route('/api/shopping/v1/lists/{listId}/lines/{lineId}', methods: ['PATCH'], requirements: ['listId' => '[a-f0-9\\-]{36}', 'lineId' => '[a-f0-9\\-]{36}'])]
    public function patchLine(string $listId, string $lineId, Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $line = $this->lists->patchLine($userId, $listId, $lineId, $payload);
        if ($line === null) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Line not found.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($line);
    }

    #[Route('/api/shopping/v1/lists/{listId}/lines/{lineId}', methods: ['DELETE'], requirements: ['listId' => '[a-f0-9\\-]{36}', 'lineId' => '[a-f0-9\\-]{36}'])]
    public function deleteLine(string $listId, string $lineId, Request $request): Response
    {
        $userId = (string) $request->attributes->get(TrustedUserSubscriber::ATTR_USER_ID);
        if (!$this->lists->deleteLine($userId, $listId, $lineId)) {
            return ErrorResponseFactory::create('NOT_FOUND', 'Line not found.', Response::HTTP_NOT_FOUND);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
