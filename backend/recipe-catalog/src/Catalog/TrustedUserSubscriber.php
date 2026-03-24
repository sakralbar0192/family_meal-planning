<?php

namespace App\Catalog;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final class TrustedUserSubscriber implements EventSubscriberInterface
{
    public const ATTR_USER_ID = 'catalog.user_id';

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 10]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->requiresTrustedUser($request)) {
            return;
        }

        $userId = $request->headers->get('X-User-Id');
        if (!\is_string($userId) || $userId === '' || !Uuid::isValid($userId)) {
            $event->setResponse(ErrorResponseFactory::create(
                'USER_REQUIRED',
                'Missing or invalid X-User-Id.',
                Response::HTTP_UNAUTHORIZED
            ));

            return;
        }

        $request->attributes->set(self::ATTR_USER_ID, $userId);
    }

    private function requiresTrustedUser(Request $request): bool
    {
        return \str_starts_with($request->getPathInfo(), '/api/catalog/v1/')
            && $request->getPathInfo() !== '/api/catalog/v1/health';
    }
}
