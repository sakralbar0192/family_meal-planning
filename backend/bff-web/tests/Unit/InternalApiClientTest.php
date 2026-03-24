<?php

namespace App\Tests\Unit;

use App\Bff\InternalApiClient;
use App\Bff\RequestContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class InternalApiClientTest extends TestCase
{
    public function testAddsCorrelationUserAndInternalHeaders(): void
    {
        $capturedOptions = null;
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedOptions) {
            $capturedOptions = [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ];

            return new MockResponse('{"ok":true}', ['http_code' => 200]);
        });

        $client = new InternalApiClient($httpClient, 'secret-token');
        $request = Request::create('/bff/v1/import/url');
        $request->attributes->set(RequestContext::ATTR_CORRELATION_ID, 'f30044a5-7349-4048-9ee7-6e118a2d5efd');
        $request->attributes->set(RequestContext::ATTR_USER_ID, 'c9870d3f-1764-43bc-ad3f-f73e30027486');
        $client->post($request, 'http://upstream.local/base', '/imports/url', ['url' => 'https://example.com']);

        self::assertNotNull($capturedOptions);
        self::assertSame('POST', $capturedOptions['method']);
        self::assertSame('http://upstream.local/base/imports/url', $capturedOptions['url']);
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

        self::assertSame('secret-token', $header($capturedOptions['options'], 'x-internal-auth'));
        self::assertSame(
            'f30044a5-7349-4048-9ee7-6e118a2d5efd',
            $header($capturedOptions['options'], 'x-correlation-id')
        );
        self::assertSame(
            'c9870d3f-1764-43bc-ad3f-f73e30027486',
            $header($capturedOptions['options'], 'x-user-id')
        );
        self::assertStringContainsString('"url":"https:\\/\\/example.com"', (string) ($capturedOptions['options']['body'] ?? ''));
    }

    public function testGetAppendsQueryString(): void
    {
        $capturedUrl = null;
        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$capturedUrl) {
            $capturedUrl = $url;

            return new MockResponse('{}', ['http_code' => 200]);
        });

        $client = new InternalApiClient($httpClient, 't');
        $request = Request::create('/bff/v1/plan/week');
        $client->get($request, 'http://plan/api/v1', '/week-plans/current', [
            'anchorDate' => '2026-03-02',
            'recipeSearch' => 'soup',
        ]);

        self::assertSame(
            'http://plan/api/v1/week-plans/current?anchorDate=2026-03-02&recipeSearch=soup',
            $capturedUrl
        );
    }
}
