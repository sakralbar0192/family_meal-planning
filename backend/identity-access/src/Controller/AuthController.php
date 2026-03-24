<?php

namespace App\Controller;

use App\Iam\AuthService;
use App\Iam\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[Route('/api/iam/v1/users/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $userId = $this->authService->register(
                (string) ($payload['email'] ?? ''),
                (string) ($payload['password'] ?? '')
            );

            return new JsonResponse(['userId' => $userId], Response::HTTP_CREATED);
        } catch (ConflictHttpException $exception) {
            return ErrorResponseFactory::create('EMAIL_EXISTS', $exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (BadRequestHttpException $exception) {
            return ErrorResponseFactory::create('VALIDATION_ERROR', $exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/iam/v1/sessions', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $payload = $request->toArray();
            $session = $this->authService->createSession(
                (string) ($payload['email'] ?? ''),
                (string) ($payload['password'] ?? '')
            );

            return new JsonResponse($session, Response::HTTP_OK);
        } catch (UnauthorizedHttpException $exception) {
            return ErrorResponseFactory::create('INVALID_CREDENTIALS', $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (BadRequestHttpException $exception) {
            return ErrorResponseFactory::create('VALIDATION_ERROR', $exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/iam/v1/sessions/{sessionId}', methods: ['GET'])]
    public function getSession(string $sessionId): JsonResponse
    {
        try {
            $session = $this->authService->validateSession($sessionId);

            return new JsonResponse($session, Response::HTTP_OK);
        } catch (NotFoundHttpException $exception) {
            return ErrorResponseFactory::create('SESSION_NOT_FOUND', $exception->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/api/iam/v1/sessions/{sessionId}', methods: ['DELETE'])]
    public function deleteSession(string $sessionId): JsonResponse
    {
        if (!$this->authService->deleteSession($sessionId)) {
            return ErrorResponseFactory::create('SESSION_NOT_FOUND', 'Session not found.', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
