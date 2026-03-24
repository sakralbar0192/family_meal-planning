<?php

namespace App\Iam;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class InternalAuthSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly string $internalAuthToken)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 20]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->requiresInternalAuth($request)) {
            return;
        }

        $token = $request->headers->get('X-Internal-Auth');
        if (!\is_string($token) || $token !== $this->internalAuthToken) {
            $event->setResponse(ErrorResponseFactory::create(
                'INTERNAL_AUTH_FAILED',
                'Missing or invalid internal auth token.',
                Response::HTTP_UNAUTHORIZED
            ));
        }
    }

    private function requiresInternalAuth(Request $request): bool
    {
        return \str_starts_with($request->getPathInfo(), '/api/iam/v1/')
            && $request->getPathInfo() !== '/api/iam/v1/health';
    }
}
