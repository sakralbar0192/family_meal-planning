<?php

namespace App\Tests\Unit;

use App\Shopping\UpstreamClient;
use App\Shopping\UpstreamUnavailableException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;

final class UpstreamClientTest extends TestCase
{
    public function testFetchAssignmentsThrowsWhenPlanningHttpError(): void
    {
        $http = new MockHttpClient(new MockResponse('nope', ['http_code' => 503]));
        $up = new UpstreamClient($http, 'tok', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');

        $this->expectException(UpstreamUnavailableException::class);
        $up->fetchAssignments(Request::create('/'), 'user-1', '2026-03-01', '2026-03-07');
    }

    public function testFetchAssignmentsReturnsEmptyItems(): void
    {
        $http = new MockHttpClient(new MockResponse('{"items":[]}', ['http_code' => 200]));
        $up = new UpstreamClient($http, 'tok', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');

        $got = $up->fetchAssignments(Request::create('/'), 'user-1', '2026-03-01', '2026-03-07');
        self::assertSame([], $got);
    }

    public function testFetchRecipeNullOnNotFound(): void
    {
        $http = new MockHttpClient(new MockResponse('{}', ['http_code' => 404]));
        $up = new UpstreamClient($http, 'tok', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');

        self::assertNull($up->fetchRecipe(Request::create('/'), 'user-1', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1'));
    }

    public function testFetchRecipeThrowsOnServerError(): void
    {
        $http = new MockHttpClient(new MockResponse('{}', ['http_code' => 500]));
        $up = new UpstreamClient($http, 'tok', 'http://planning.test/api/planning/v1', 'http://catalog.test/api/catalog/v1');

        $this->expectException(UpstreamUnavailableException::class);
        $up->fetchRecipe(Request::create('/'), 'user-1', 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1');
    }
}
