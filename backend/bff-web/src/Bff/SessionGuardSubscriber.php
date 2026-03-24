<?php

namespace App\Bff;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SessionGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $iamBaseUri,
        private readonly string $internalAuth,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 10],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($event->hasResponse()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isProtectedRoute($request)) {
            return;
        }

        $sessionId = $request->cookies->get('session_id');
        if (!\is_string($sessionId) || $sessionId === '') {
            $event->setResponse(ErrorResponseFactory::create(
                'AUTH_REQUIRED',
                'Session is required.',
                Response::HTTP_UNAUTHORIZED
            ));

            return;
        }

        try {
            $response = $this->httpClient->request(
                'GET',
                \rtrim($this->iamBaseUri, '/').'/sessions/'.\rawurlencode($sessionId),
                [
                    'headers' => $this->buildHeaders($request),
                ]
            );

            $status = $response->getStatusCode();
            if ($status !== Response::HTTP_OK) {
                $event->setResponse(ErrorResponseFactory::create(
                    'AUTH_INVALID_SESSION',
                    'Session is invalid or expired.',
                    Response::HTTP_UNAUTHORIZED
                ));

                return;
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->toArray(false);
            $userId = $payload['userId'] ?? null;
            if (!\is_string($userId) || $userId === '') {
                $event->setResponse(ErrorResponseFactory::create(
                    'AUTH_INVALID_SESSION',
                    'Session response is missing userId.',
                    Response::HTTP_UNAUTHORIZED
                ));

                return;
            }

            $request->attributes->set(RequestContext::ATTR_USER_ID, $userId);
        } catch (TransportExceptionInterface) {
            $event->setResponse(ErrorResponseFactory::create(
                'UPSTREAM_UNAVAILABLE',
                'Identity service is unavailable.',
                Response::HTTP_BAD_GATEWAY
            ));
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(Request $request): array
    {
        $headers = [
            'X-Internal-Auth' => $this->internalAuth,
        ];

        $correlationId = $request->attributes->get(RequestContext::ATTR_CORRELATION_ID);
        if (\is_string($correlationId) && $correlationId !== '') {
            $headers['X-Correlation-Id'] = $correlationId;
        }

        return $headers;
    }

    private function isProtectedRoute(Request $request): bool
    {
        $path = $request->getPathInfo();
        if (!\str_starts_with($path, '/bff/v1/')) {
            return false;
        }

        if ($request->isMethod(Request::METHOD_OPTIONS)) {
            return false;
        }

        if ($path === '/bff/v1/health') {
            return false;
        }

        if ($path === '/bff/v1/auth/login' && $request->isMethod(Request::METHOD_POST)) {
            return false;
        }

        if ($path === '/bff/v1/auth/register' && $request->isMethod(Request::METHOD_POST)) {
            return false;
        }

        return true;
    }
}
