<?php

namespace App\Tests\Unit;

use App\Bff\RequestContext;
use App\Bff\SessionGuardSubscriber;
use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class SessionGuardSubscriberTest extends TestCase
{
    public function testReturnsUnauthorizedWhenCookieMissing(): void
    {
        $subscriber = new SessionGuardSubscriber(
            new MockHttpClient([new MockResponse('{"userId":"x"}')]),
            'http://iam.local/api/iam/v1',
            'secret-token'
        );
        $request = Request::create('/bff/v1/import/url', Request::METHOD_POST);
        $event = new RequestEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onRequest($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(401, $event->getResponse()?->getStatusCode());
    }

    public function testRegisterRouteDoesNotRequireSession(): void
    {
        $subscriber = new SessionGuardSubscriber(
            new MockHttpClient([new MockResponse('', ['http_code' => 500])]),
            'http://iam.local/api/iam/v1',
            'secret-token'
        );
        $request = Request::create('/bff/v1/auth/register', Request::METHOD_POST);
        $event = new RequestEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testSetsUserIdOnValidSession(): void
    {
        $captured = null;
        $subscriber = new SessionGuardSubscriber(
            new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
                $captured = ['method' => $method, 'url' => $url, 'options' => $options];

                return new MockResponse('{"userId":"8061ba4d-4f10-4f85-af26-d339230f6bb0"}', ['http_code' => 200]);
            }),
            'http://iam.local/api/iam/v1',
            'secret-token'
        );
        $request = Request::create('/bff/v1/import/url', Request::METHOD_POST);
        $request->attributes->set(RequestContext::ATTR_CORRELATION_ID, '1517a2f1-53cc-411b-af87-cd06fbf934e4');
        $request->cookies->set('session_id', 'sess-123');
        $event = new RequestEvent($this->kernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber->onRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame('8061ba4d-4f10-4f85-af26-d339230f6bb0', $request->attributes->get(RequestContext::ATTR_USER_ID));
        self::assertSame('GET', $captured['method']);
        self::assertSame('http://iam.local/api/iam/v1/sessions/sess-123', $captured['url']);
        $header = static function (array $options, string $name): ?string {
            $lower = \strtolower($name);
            if (isset($options['headers']) && \is_array($options['headers'])) {
                $headers = \array_change_key_case($options['headers']);
                if (isset($headers[$lower]) && \is_string($headers[$lower])) {
                    return $headers[$lower];
                }
            }

            if (isset($options['normalized_headers'][$lower][0]) && \is_string($options['normalized_headers'][$lower][0])) {
                $line = $options['normalized_headers'][$lower][0];
                if (\str_contains($line, ': ')) {
                    return \explode(': ', $line, 2)[1];
                }

                return $line;
            }

            return null;
        };

        self::assertSame('secret-token', $header($captured['options'], 'x-internal-auth'));
        self::assertSame(
            '1517a2f1-53cc-411b-af87-cd06fbf934e4',
            $header($captured['options'], 'x-correlation-id')
        );
    }

    private function kernel(): Kernel
    {
        return new Kernel('test', true);
    }
}
