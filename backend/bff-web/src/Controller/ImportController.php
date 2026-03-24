<?php

namespace App\Controller;

use App\Bff\InternalApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ImportController extends BaseProxyController
{
    public function __construct(
        private readonly InternalApiClient $internalApiClient,
        private readonly string $importBaseUri,
    ) {
    }

    #[Route('/bff/v1/import/url', name: 'bff_import_url', methods: ['POST'])]
    public function importByUrl(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->post($request, $this->importBaseUri, '/imports/url', $payload);

        return $this->toJsonResponse($upstream);
    }
}
