<?php

namespace App\Tests\Integration;

use App\Shopping\ShoppingListRepository;
use App\Shopping\UpstreamClient;
use App\Shopping\UpstreamUnavailableException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class ShoppingBuildWithMockUpstreamsTest extends KernelTestCase
{
    private ShoppingListRepository $lists;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->lists = static::getContainer()->get(ShoppingListRepository::class);

        try {
            $this->lists->createSchema();
            $pdo = static::getContainer()->get(\App\Shopping\Database::class)->pdo();
            $pdo->exec('TRUNCATE TABLE shopping_lists CASCADE');
        } catch (\Throwable $e) {
            self::markTestSkipped('Postgres is not available for integration tests: '.$e->getMessage());
        }
    }

    public function testBuildAggregatesIngredientsFromPlan(): void
    {
        $r1 = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb1';
        $r2 = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbb2';
        $http = new MockHttpClient(function (string $method, string $url) use ($r1, $r2) {
            if (str_contains($url, '/assignments/plan')) {
                return new MockResponse(\json_encode([
                    'items' => [
                        ['date' => '2026-03-02', 'slotCode' => 'DINNER', 'recipeId' => $r1],
                        ['date' => '2026-03-03', 'slotCode' => 'DINNER', 'recipeId' => $r2],
                    ],
                ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            }
            if (str_contains($url, '/recipes/'.$r1)) {
                return new MockResponse(\json_encode([
                    'id' => $r1,
                    'ingredients' => [
                        ['name' => 'Мука', 'quantity' => 200, 'unit' => 'г', 'productCategory' => 'бакалея'],
                        ['name' => 'Молоко', 'quantity' => 200, 'unit' => 'мл', 'productCategory' => 'молочные'],
                    ],
                ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            }
            if (str_contains($url, '/recipes/'.$r2)) {
                return new MockResponse(\json_encode([
                    'id' => $r2,
                    'ingredients' => [
                        ['name' => 'мука', 'quantity' => 100, 'unit' => 'г', 'productCategory' => 'бакалея'],
                        ['name' => 'Молоко', 'quantity' => 1, 'unit' => 'л', 'productCategory' => 'молочные'],
                    ],
                ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            }

            return new MockResponse('{}', ['http_code' => 404]);
        });

        $up = new UpstreamClient($http, 'dev-internal-token', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');
        $userId = 'a0000000-0000-4000-8000-000000000099';
        $result = $this->lists->build($userId, '2026-03-02', '2026-03-08', $up, Request::create('/'));

        self::assertFalse($result['empty']);
        $detail = $this->lists->getListDetail($userId, $result['listId']);
        self::assertNotNull($detail);
        $byNameUnit = [];
        foreach ($detail['lines'] as $line) {
            $byNameUnit[$line['displayName'].'|'.($line['unit'] ?? '')] = $line;
        }
        self::assertSame(300.0, $byNameUnit['Мука|г']['quantity']);
        self::assertArrayHasKey('Молоко|мл', $byNameUnit);
        self::assertArrayHasKey('Молоко|л', $byNameUnit);
    }

    public function testBuildEmptyWhenPlanHasNoAssignments(): void
    {
        $http = new MockHttpClient(function (string $method, string $url) {
            if (str_contains($url, '/assignments/plan')) {
                return new MockResponse(\json_encode(['items' => []], JSON_THROW_ON_ERROR), ['http_code' => 200]);
            }

            return new MockResponse('{}', ['http_code' => 500]);
        });

        $up = new UpstreamClient($http, 'dev-internal-token', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');
        $userId = 'a0000000-0000-4000-8000-000000000098';
        $result = $this->lists->build($userId, '2026-03-02', '2026-03-08', $up, Request::create('/'));

        self::assertTrue($result['empty']);
        $detail = $this->lists->getListDetail($userId, $result['listId']);
        self::assertNotNull($detail);
        self::assertSame([], $detail['lines']);
    }

    public function testBuildThrowsWhenPlanningUnavailable(): void
    {
        $http = new MockHttpClient(function () {
            return new MockResponse('nope', ['http_code' => 503]);
        });
        $up = new UpstreamClient($http, 'dev-internal-token', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');

        $this->expectException(UpstreamUnavailableException::class);
        $this->lists->build(
            'a0000000-0000-4000-8000-000000000097',
            '2026-03-02',
            '2026-03-08',
            $up,
            Request::create('/'),
        );
    }
}
