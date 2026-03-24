<?php

namespace App\Tests\Integration;

use App\Iam\UserRepository;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IamHttpTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepository $users */
        $users = $container->get(UserRepository::class);
        /** @var Client $redis */
        $redis = $container->get(Client::class);

        try {
            $users->createSchema();
            $pdo = $container->get(\App\Iam\Database::class)->pdo();
            $pdo->exec('TRUNCATE TABLE iam_users');
            $redis->flushdb();
        } catch (\Throwable $e) {
            self::markTestSkipped('Postgres/Redis are not available for integration tests: '.$e->getMessage());
        }
    }

    public function testRequiresInternalAuthHeader(): void
    {
        $this->client->request('POST', '/api/iam/v1/sessions', server: ['CONTENT_TYPE' => 'application/json'], content: '{"email":"u@e.com","password":"secret123"}');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRegisterLoginValidateAndLogoutFlow(): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_INTERNAL_AUTH' => 'dev-internal-token'];

        $this->client->request('POST', '/api/iam/v1/users/register', server: $headers, content: '{"email":"user@example.com","password":"secret123"}');
        self::assertResponseStatusCodeSame(201);
        $register = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('userId', $register);

        $this->client->request('POST', '/api/iam/v1/sessions', server: $headers, content: '{"email":"user@example.com","password":"secret123"}');
        self::assertResponseStatusCodeSame(200);
        $session = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('sessionId', $session);
        self::assertArrayHasKey('expiresAt', $session);

        $this->client->request('GET', '/api/iam/v1/sessions/'.$session['sessionId'], server: ['HTTP_X_INTERNAL_AUTH' => 'dev-internal-token']);
        self::assertResponseStatusCodeSame(200);
        $valid = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($register['userId'], $valid['userId']);

        $this->client->request('DELETE', '/api/iam/v1/sessions/'.$session['sessionId'], server: ['HTTP_X_INTERNAL_AUTH' => 'dev-internal-token']);
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/iam/v1/sessions/'.$session['sessionId'], server: ['HTTP_X_INTERNAL_AUTH' => 'dev-internal-token']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testChangePasswordWithTrustedUserHeader(): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_INTERNAL_AUTH' => 'dev-internal-token'];

        $this->client->request('POST', '/api/iam/v1/users/register', server: $headers, content: '{"email":"pw@example.com","password":"oldsecret12"}');
        self::assertResponseStatusCodeSame(201);
        $register = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $userId = $register['userId'];

        $this->client->request(
            'PATCH',
            '/api/iam/v1/users/me/password',
            server: \array_merge($headers, ['HTTP_X_USER_ID' => $userId]),
            content: '{"currentPassword":"oldsecret12","newPassword":"newsecret12"}'
        );
        self::assertResponseStatusCodeSame(204);

        $this->client->request('POST', '/api/iam/v1/sessions', server: $headers, content: '{"email":"pw@example.com","password":"newsecret12"}');
        self::assertResponseStatusCodeSame(200);
    }

    public function testHealthReturns200WhenDependenciesUp(): void
    {
        $this->client->request('GET', '/api/iam/v1/health');
        self::assertResponseStatusCodeSame(200);
    }
}
