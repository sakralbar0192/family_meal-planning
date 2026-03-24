<?php

namespace App\Controller;

use App\Bff\InternalApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PlanController extends BaseProxyController
{
    public function __construct(
        private readonly InternalApiClient $internalApiClient,
        private readonly string $planningBaseUri,
    ) {
    }

    #[Route('/bff/v1/plan/week', name: 'bff_plan_week', methods: ['GET'])]
    public function week(Request $request): JsonResponse
    {
        $query = [];
        foreach (['anchorDate', 'focusDate', 'recipeSearch'] as $key) {
            if ($request->query->has($key)) {
                $query[$key] = $request->query->get($key);
            }
        }

        $upstream = $this->internalApiClient->get($request, $this->planningBaseUri, '/week-plans/current', $query);

        return $this->toJsonResponse($upstream);
    }

    #[Route('/bff/v1/plan/slots/{slotId}', name: 'bff_patch_slot', methods: ['PATCH'])]
    public function patchSlot(string $slotId, Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->patch($request, $this->planningBaseUri, '/slots/'.$slotId, $payload);

        return $this->toJsonResponse($upstream);
    }
}
