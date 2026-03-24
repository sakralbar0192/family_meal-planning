<?php

namespace App\Bff;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class InternalApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $internalAuth,
    ) {
    }

    /**
     * @param array<string, mixed> $body
     */
    public function post(Request $request, string $baseUri, string $path, array $body): ResponseInterface
    {
        return $this->request($request, 'POST', $baseUri, $path, ['json' => $body]);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function patch(Request $request, string $baseUri, string $path, array $body): ResponseInterface
    {
        return $this->request($request, 'PATCH', $baseUri, $path, ['json' => $body]);
    }

    public function get(Request $request, string $baseUri, string $path): ResponseInterface
    {
        return $this->request($request, 'GET', $baseUri, $path, []);
    }

    public function delete(Request $request, string $baseUri, string $path): ResponseInterface
    {
        return $this->request($request, 'DELETE', $baseUri, $path, []);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function request(Request $request, string $method, string $baseUri, string $path, array $options): ResponseInterface
    {
        $headers = [
            'X-Internal-Auth' => $this->internalAuth,
        ];

        $correlationId = $request->attributes->get(RequestContext::ATTR_CORRELATION_ID);
        if (\is_string($correlationId) && $correlationId !== '') {
            $headers['X-Correlation-Id'] = $correlationId;
        }

        $userId = $request->attributes->get(RequestContext::ATTR_USER_ID);
        if (\is_string($userId) && $userId !== '') {
            $headers['X-User-Id'] = $userId;
        }

        $options['headers'] = $headers;

        return $this->httpClient->request($method, \rtrim($baseUri, '/').'/'.\ltrim($path, '/'), $options);
    }
}
