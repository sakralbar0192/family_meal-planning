<?php

namespace App\Tests\Unit;

use App\Bff\CorrelationIdSubscriber;
use App\Bff\RequestContext;
use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class CorrelationIdSubscriberTest extends TestCase
{
    public function testUsesIncomingCorrelationIdWhenValid(): void
    {
        $subscriber = new CorrelationIdSubscriber();
        $request = Request::create('/bff/v1/health');
        $request->headers->set('X-Correlation-Id', 'b658f62e-0f94-4335-a78c-440fb08f08f1');
        $event = new RequestEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onRequest($event);

        self::assertSame(
            'b658f62e-0f94-4335-a78c-440fb08f08f1',
            $request->attributes->get(RequestContext::ATTR_CORRELATION_ID)
        );
    }

    public function testGeneratesCorrelationIdWhenIncomingInvalid(): void
    {
        $subscriber = new CorrelationIdSubscriber();
        $request = Request::create('/bff/v1/health');
        $request->headers->set('X-Correlation-Id', 'invalid');
        $event = new RequestEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onRequest($event);
        $generated = $request->attributes->get(RequestContext::ATTR_CORRELATION_ID);

        self::assertIsString($generated);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $generated
        );
    }

    public function testEchoesCorrelationIdToResponseHeader(): void
    {
        $subscriber = new CorrelationIdSubscriber();
        $request = Request::create('/bff/v1/health');
        $request->attributes->set(RequestContext::ATTR_CORRELATION_ID, '3db7c79e-4cd0-46a8-88b3-b010db18fc8d');
        $response = new Response();
        $event = new ResponseEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $subscriber->onResponse($event);

        self::assertSame('3db7c79e-4cd0-46a8-88b3-b010db18fc8d', $response->headers->get('X-Correlation-Id'));
    }

    private function kernel(): Kernel
    {
        return new Kernel('test', true);
    }
}
