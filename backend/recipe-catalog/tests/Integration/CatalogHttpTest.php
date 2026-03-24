<?php

namespace App\Tests\Integration;

use App\Catalog\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CatalogHttpTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = static::getContainer();

        /** @var RecipeRepository $recipes */
        $recipes = $container->get(RecipeRepository::class);

        try {
            $recipes->createSchema();
            $pdo = $container->get(\App\Catalog\Database::class)->pdo();
            $pdo->exec('TRUNCATE TABLE catalog_recipes');
        } catch (\Throwable $e) {
            self::markTestSkipped('Postgres is not available for integration tests: '.$e->getMessage());
        }
    }

    public function testRequiresInternalAuth(): void
    {
        $this->client->request('GET', '/api/catalog/v1/recipes');
        self::assertResponseStatusCodeSame(401);
    }

    public function testRequiresUserId(): void
    {
        $this->client->request('GET', '/api/catalog/v1/recipes', server: ['HTTP_X_INTERNAL_AUTH' => 'dev-internal-token']);
        self::assertResponseStatusCodeSame(401);
    }

    public function testCrudFlow(): void
    {
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_INTERNAL_AUTH' => 'dev-internal-token',
            'HTTP_X_USER_ID' => 'a0000000-0000-4000-8000-000000000001',
        ];

        $this->client->request(
            'POST',
            '/api/catalog/v1/recipes',
            server: $headers,
            content: '{"title":"Soup","ingredients":[{"name":"Water","quantity":500,"unit":"ml","productCategory":"beverages"}]}'
        );
        self::assertResponseStatusCodeSame(201);
        $created = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('id', $created);
        $id = $created['id'];

        $this->client->request('GET', '/api/catalog/v1/recipes', server: $headers);
        self::assertResponseStatusCodeSame(200);
        $list = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $list['total']);

        $this->client->request('GET', '/api/catalog/v1/recipes/'.$id, server: $headers);
        self::assertResponseStatusCodeSame(200);

        $this->client->request(
            'PATCH',
            '/api/catalog/v1/recipes/'.$id,
            server: $headers,
            content: '{"title":"Rich soup"}'
        );
        self::assertResponseStatusCodeSame(200);
        $patched = \json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Rich soup', $patched['title']);

        $this->client->request('DELETE', '/api/catalog/v1/recipes/'.$id, server: $headers);
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/catalog/v1/recipes/'.$id, server: $headers);
        self::assertResponseStatusCodeSame(404);
    }

    public function testHealth(): void
    {
        $this->client->request('GET', '/api/catalog/v1/health');
        self::assertResponseStatusCodeSame(200);
    }
}
