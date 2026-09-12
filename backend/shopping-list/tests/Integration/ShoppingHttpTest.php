<?php

namespace App\Tests\Integration;

use App\Shopping\ShoppingListRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ShoppingHttpTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();

        /** @var ShoppingListRepository $lists */
        $lists = $container->get(ShoppingListRepository::class);

        try {
            $lists->createSchema();
            $pdo = $container->get(\App\Shopping\Database::class)->pdo();
            $pdo->exec('TRUNCATE TABLE shopping_lists CASCADE');
        } catch (\Throwable $e) {
            self::markTestSkipped('Postgres is not available for integration tests: '.$e->getMessage());
        }
    }

    public function testRequiresInternalAuth(): void
    {
        $this->client->request('POST', '/api/shopping/v1/lists/build', server: ['CONTENT_TYPE' => 'application/json'], content: '{"from":"2026-03-01","to":"2026-03-07"}');
        self::assertResponseStatusCodeSame(401);
    }

    public function testHealth(): void
    {
        $this->client->request('GET', '/api/shopping/v1/health');
        self::assertResponseStatusCodeSame(200);
    }

    public function testBuildEmptyPeriodWhenUpstreamsUnavailable(): void
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_INTERNAL_AUTH' => 'dev-internal-token',
            'HTTP_X_USER_ID' => 'a0000000-0000-4000-8000-000000000001',
        ];
        $this->client->request(
            'POST',
            '/api/shopping/v1/lists/build',
            server: $headers,
            content: '{"from":"2026-03-01","to":"2026-03-07"}'
        );
        self::assertResponseStatusCodeSame(200);
        $body = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($body['empty']);
        self::assertArrayHasKey('listId', $body);

        $this->client->request('GET', '/api/shopping/v1/lists/'.$body['listId'], server: $headers);
        self::assertResponseStatusCodeSame(200);
        $detail = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($detail['empty']);
        self::assertSame([], $detail['lines']);
    }
}
