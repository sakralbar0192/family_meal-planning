<?php

namespace App\Controller;

use App\Iam\AuthService;
use App\Iam\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    #[Route('/api/iam/v1/users/me/password', methods: ['PATCH'])]
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->headers->get('X-User-Id');
        if (!\is_string($userId) || $userId === '') {
            return ErrorResponseFactory::create(
                'UNAUTHORIZED',
                'X-User-Id is required.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = $request->toArray();
            $this->authService->changePassword(
                $userId,
                (string) ($payload['currentPassword'] ?? ''),
                (string) ($payload['newPassword'] ?? '')
            );

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (BadRequestHttpException $exception) {
            return ErrorResponseFactory::create(
                'VALIDATION_ERROR',
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (UnauthorizedHttpException $exception) {
            return ErrorResponseFactory::create(
                'UNAUTHORIZED',
                $exception->getMessage(),
                Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
