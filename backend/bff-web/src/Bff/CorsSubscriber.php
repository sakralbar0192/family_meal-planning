<?php

namespace App\Bff;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * CORS для браузерного фронта (Vite на 5173–5176): credentials + echo Origin из allow-list.
 * Список origin: env BFF_CORS_ALLOW_ORIGIN (CSV). Пусто — заголовки не выставляются.
 */
final class CorsSubscriber implements EventSubscriberInterface
{
    private const ALLOW_METHODS = 'GET, POST, PATCH, DELETE, OPTIONS';

    private const ALLOW_HEADERS = 'Content-Type, X-Correlation-Id, X-Requested-With';

    public function __construct(
        private readonly string $allowedOriginsCsv,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Выше RouterListener (32), иначе OPTIONS получит 405 до обработки preflight.
            KernelEvents::REQUEST => ['onKernelRequest', 40],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!\str_starts_with($request->getPathInfo(), '/bff/v1')) {
            return;
        }

        if (!$request->isMethod(Request::METHOD_OPTIONS)) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if (!$this->isOriginAllowed($origin)) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->applyCorsToResponse($request, $response);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!\str_starts_with($request->getPathInfo(), '/bff/v1')) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if (!$this->isOriginAllowed($origin)) {
            return;
        }

        $this->applyCorsToResponse($request, $event->getResponse());
    }

    private function applyCorsToResponse(Request $request, Response $response): void
    {
        $origin = $request->headers->get('Origin');
        if (!\is_string($origin) || $origin === '') {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', self::ALLOW_METHODS);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOW_HEADERS);
        $response->headers->set('Access-Control-Max-Age', '600');
        $response->headers->set('Vary', 'Origin');
    }

    private function isOriginAllowed(?string $origin): bool
    {
        if (!\is_string($origin) || $origin === '') {
            return false;
        }

        foreach ($this->allowedOrigins() as $allowed) {
            if ($allowed === $origin) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function allowedOrigins(): array
    {
        $csv = \trim($this->allowedOriginsCsv);
        if ($csv === '') {
            return [];
        }

        $parts = \preg_split('/\s*,\s*/', $csv) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = \trim((string) $p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return $out;
    }
}
