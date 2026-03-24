<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BffRealIamFlowTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        if ($_ENV['APP_ENV'] !== 'iam') {
            self::markTestSkipped('Real IAM flow test runs only with APP_ENV=iam.');
        }

        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $httpClient = static::getContainer()->get(HttpClientInterface::class);
        try {
            $response = $httpClient->request('GET', 'http://127.0.0.1:28081/api/iam/v1/health')->getStatusCode();
            if ($response !== 200) {
                self::markTestSkipped('IAM service is unavailable.');
            }
        } catch (\Throwable) {
            self::markTestSkipped('IAM service is unavailable.');
        }
    }

    public function testBffLoginSessionValidateAndLogoutAgainstRealIam(): void
    {
        $httpClient = static::getContainer()->get(HttpClientInterface::class);
        $email = 'real-iam-'.\bin2hex(\random_bytes(4)).'@example.com';
        $password = 'secret123';

        $registerStatus = $httpClient->request(
            'POST',
            'http://127.0.0.1:28081/api/iam/v1/users/register',
            [
                'headers' => ['X-Internal-Auth' => 'dev-internal-token'],
                'json' => ['email' => $email, 'password' => $password],
            ]
        )->getStatusCode();
        self::assertTrue(\in_array($registerStatus, [201, 409], true));

        $this->client->request(
            'POST',
            '/bff/v1/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: \sprintf('{"email":"%s","password":"%s"}', $email, $password)
        );
        self::assertResponseStatusCodeSame(200);
        $payload = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('userId', $payload);
        $cookieHeader = $this->client->getResponse()->headers->get('set-cookie');
        self::assertNotNull($cookieHeader);
        \preg_match('/session_id=([^;]+)/', $cookieHeader, $matches);
        self::assertNotEmpty($matches[1] ?? null);
        $sessionId = $matches[1];

        $this->client->getCookieJar()->set(new Cookie('session_id', $sessionId));
        $this->client->request('POST', '/bff/v1/auth/logout');
        self::assertResponseStatusCodeSame(204);
    }
}
