<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CorsTest extends WebTestCase
{
    public function testPreflightOptionsReturnsNoContentWithCorsHeaders(): void
    {
        $client = static::createClient();
        $client->request(
            'OPTIONS',
            '/bff/v1/auth/login',
            server: [
                'HTTP_ORIGIN' => 'http://localhost:5173',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $res = $client->getResponse();
        self::assertSame('http://localhost:5173', $res->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('true', $res->headers->get('Access-Control-Allow-Credentials'));
        self::assertStringContainsString('POST', (string) $res->headers->get('Access-Control-Allow-Methods'));
    }

    public function testGetHealthIncludesCorsHeadersWhenOriginAllowed(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/bff/v1/health',
            server: ['HTTP_ORIGIN' => 'http://localhost:5173']
        );

        self::assertResponseIsSuccessful();
        $res = $client->getResponse();
        self::assertSame('http://localhost:5173', $res->headers->get('Access-Control-Allow-Origin'));
        self::assertSame('true', $res->headers->get('Access-Control-Allow-Credentials'));
    }

    public function testDisallowedOriginDoesNotReceiveAllowOriginHeader(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/bff/v1/health',
            server: ['HTTP_ORIGIN' => 'https://evil.example']
        );

        self::assertResponseIsSuccessful();
        self::assertNull($client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }
}
