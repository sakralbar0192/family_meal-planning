<?php

namespace App\Controller;

use App\Bff\InternalApiClient;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends BaseProxyController
{
    public function __construct(
        private readonly InternalApiClient $internalApiClient,
        private readonly string $iamBaseUri,
    ) {
    }

    #[Route('/bff/v1/auth/login', name: 'bff_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->toArray();
        $upstream = $this->internalApiClient->post($request, $this->iamBaseUri, '/sessions', $payload);
        $response = $this->toJsonResponse($upstream);

        if ($response->getStatusCode() === Response::HTTP_OK) {
            /** @var array<string, mixed> $data */
            $data = \json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $sessionId = $data['sessionId'] ?? null;
            if (\is_string($sessionId) && $sessionId !== '') {
                $response->headers->setCookie(
                    Cookie::create('session_id', $sessionId, 0, '/', null, false, true, false, Cookie::SAMESITE_LAX)
                );
            }
        }

        return $response;
    }

    #[Route('/bff/v1/auth/logout', name: 'bff_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $sessionId = $request->cookies->get('session_id');
        if (\is_string($sessionId) && $sessionId !== '') {
            $this->internalApiClient->delete($request, $this->iamBaseUri, '/sessions/'.\rawurlencode($sessionId));
        }

        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie('session_id', '/');

        return $response;
    }
}
