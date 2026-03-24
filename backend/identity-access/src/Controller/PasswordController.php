<?php

namespace App\Controller;

use App\Iam\ErrorResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PasswordController
{
    #[Route('/api/iam/v1/users/me/password', methods: ['PATCH'])]
    public function __invoke(): JsonResponse
    {
        return ErrorResponseFactory::create(
            'NOT_IMPLEMENTED',
            'Password change is not implemented in this stage.',
            Response::HTTP_NOT_IMPLEMENTED
        );
    }
}
