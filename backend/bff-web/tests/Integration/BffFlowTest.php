<?php

namespace App\Tests\Integration;

use App\Tests\Double\FakeUpstreamHttpClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class BffFlowTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FakeUpstreamHttpClient::reset();
    }

    public function testProtectedRouteReturns401WithoutSessionCookie(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/bff/v1/import/url',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"url":"https://site.example/recipe"}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $body = \json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('AUTH_REQUIRED', $body['code']);
    }

    public function testRegisterProxiesToIam(): void
    {
        FakeUpstreamHttpClient::queueResponses([
            [
                'method' => 'POST',
                'url' => 'http://localhost:8081/api/iam/v1/users/register',
                'status' => 201,
                'body' => '{"userId":"c3f11572-1d8b-4f6b-86d0-3f4f74f2f265"}',
            ],
        ]);

        $client = static::createClient();
        $client->request(
            'POST',
            '/bff/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"email":"new@example.com","password":"secret123"}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $body = \json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('c3f11572-1d8b-4f6b-86d0-3f4f74f2f265', $body['userId']);
    }

    public function testChangePasswordProxiesPatchToIam(): void
    {
        FakeUpstreamHttpClient::queueResponses([
            [
                'method' => 'GET',
                'url' => 'http://localhost:8081/api/iam/v1/sessions/sess-123',
                'status' => 200,
                'body' => '{"userId":"24f74de4-a50f-4eb4-b336-44f10a158ad4"}',
            ],
            [
                'method' => 'PATCH',
                'url' => 'http://localhost:8081/api/iam/v1/users/me/password',
                'status' => 204,
                'body' => '',
            ],
        ]);

        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie('session_id', 'sess-123'));
        $client->request(
            'POST',
            '/bff/v1/auth/password',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"currentPassword":"old-secret12","newPassword":"new-secret12"}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $calls = FakeUpstreamHttpClient::calls();
        self::assertCount(2, $calls);
        self::assertSame('PATCH', $calls[1]['method']);
        $opts = $calls[1]['options'];
        $headers = $opts['normalized_headers'] ?? [];
        $userLine = $headers['x-user-id'][0] ?? '';
        self::assertStringContainsString('24f74de4-a50f-4eb4-b336-44f10a158ad4', $userLine);
    }

    public function testLoginProxiesToIamAndSetsSessionCookie(): void
    {
        FakeUpstreamHttpClient::queueResponses([
            [
                'method' => 'POST',
                'url' => 'http://localhost:8081/api/iam/v1/sessions',
                'status' => 200,
                'body' => '{"sessionId":"sess-123","userId":"c3f11572-1d8b-4f6b-86d0-3f4f74f2f265","expiresAt":"2030-01-01T00:00:00Z"}',
            ],
        ]);

        $client = static::createClient();
        $client->request(
            'POST',
            '/bff/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CORRELATION_ID' => '4e6bedc4-1ee6-4864-9f2b-5e721320be5e'],
            content: '{"email":"user@example.com","password":"secret"}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertTrue($client->getResponse()->headers->has('set-cookie'));
        self::assertSame('4e6bedc4-1ee6-4864-9f2b-5e721320be5e', $client->getResponse()->headers->get('X-Correlation-Id'));
    }

    public function testImportAndShoppingBuildPassthroughWithCorrelationId(): void
    {
        FakeUpstreamHttpClient::queueResponses([
            [
                'method' => 'GET',
                'url' => 'http://localhost:8081/api/iam/v1/sessions/sess-123',
                'status' => 200,
                'body' => '{"userId":"24f74de4-a50f-4eb4-b336-44f10a158ad4"}',
            ],
            [
                'method' => 'POST',
                'url' => 'http://localhost:8083/api/import/v1/imports/url',
                'status' => 200,
                'body' => '{"title":"Imported draft"}',
            ],
            [
                'method' => 'GET',
                'url' => 'http://localhost:8081/api/iam/v1/sessions/sess-123',
                'status' => 200,
                'body' => '{"userId":"24f74de4-a50f-4eb4-b336-44f10a158ad4"}',
            ],
            [
                'method' => 'POST',
                'url' => 'http://localhost:8085/api/shopping/v1/lists/build',
                'status' => 200,
                'body' => '{"listId":"77abf34d-8dc0-4442-9f54-23fb65fb7cf6","from":"2026-03-01","to":"2026-03-07","replaced":false,"empty":false}',
            ],
        ]);

        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie('session_id', 'sess-123'));
        $client->request(
            'POST',
            '/bff/v1/import/url',
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CORRELATION_ID' => '56091445-08b7-4dfb-be2d-d0179f8cd9f2'],
            content: '{"url":"https://site.example/recipe"}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame('56091445-08b7-4dfb-be2d-d0179f8cd9f2', $client->getResponse()->headers->get('X-Correlation-Id'));

        $callsAfterImport = FakeUpstreamHttpClient::calls();
        self::assertSame('GET', $callsAfterImport[0]['method']);
        self::assertSame(
            '56091445-08b7-4dfb-be2d-d0179f8cd9f2',
            self::headerFromRequestOptions($callsAfterImport[0]['options'], 'x-correlation-id')
        );

        $client->request(
            'POST',
            '/bff/v1/shopping/build',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"from":"2026-03-01","to":"2026-03-07"}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $body = \json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('77abf34d-8dc0-4442-9f54-23fb65fb7cf6', $body['listId']);
    }

    public function testGetWeekPlanProxiesWithQueryString(): void
    {
        FakeUpstreamHttpClient::queueResponses([
            [
                'method' => 'GET',
                'url' => 'http://localhost:8081/api/iam/v1/sessions/sess-123',
                'status' => 200,
                'body' => '{"userId":"24f74de4-a50f-4eb4-b336-44f10a158ad4"}',
            ],
            [
                'method' => 'GET',
                'url' => 'http://localhost:8084/api/planning/v1/week-plans/current?anchorDate=2026-03-02&recipeSearch=tomato',
                'status' => 200,
                'body' => '{"weekStart":"2026-03-02","weekEnd":"2026-03-08","slots":[]}',
            ],
        ]);

        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie('session_id', 'sess-123'));
        $client->request(
            'GET',
            '/bff/v1/plan/week?anchorDate=2026-03-02&recipeSearch=tomato'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $body = \json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('2026-03-02', $body['weekStart']);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function headerFromRequestOptions(array $options, string $name): ?string
    {
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
    }
}
