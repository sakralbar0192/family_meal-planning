<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class BaseProxyController
{
    protected function toJsonResponse(ResponseInterface $upstreamResponse): JsonResponse
    {
        $status = $upstreamResponse->getStatusCode();
        $content = $upstreamResponse->getContent(false);
        if ($content === '') {
            return new JsonResponse(null, $status);
        }

        /** @var mixed $decoded */
        $decoded = \json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return new JsonResponse($decoded, $status);
    }
}
