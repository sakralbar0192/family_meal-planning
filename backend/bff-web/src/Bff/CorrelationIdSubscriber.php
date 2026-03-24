<?php

namespace App\Bff;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final class CorrelationIdSubscriber implements EventSubscriberInterface
{
    private const HEADER = 'X-Correlation-Id';

    public static function getSubscribedEvents(): array
    {
        return [
            // Run before SessionGuardSubscriber (priority 10) so IAM session validation gets X-Correlation-Id.
            KernelEvents::REQUEST => ['onRequest', 20],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $incoming = $request->headers->get(self::HEADER);
        $correlationId = $this->resolveCorrelationId($incoming);
        $request->attributes->set(RequestContext::ATTR_CORRELATION_ID, $correlationId);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $correlationId = $request->attributes->get(RequestContext::ATTR_CORRELATION_ID);
        if (!\is_string($correlationId) || $correlationId === '') {
            return;
        }

        $response->headers->set(self::HEADER, $correlationId);
    }

    private function resolveCorrelationId(?string $incoming): string
    {
        if (\is_string($incoming) && Uuid::isValid($incoming)) {
            return $incoming;
        }

        return Uuid::v4()->toRfc4122();
    }
}
