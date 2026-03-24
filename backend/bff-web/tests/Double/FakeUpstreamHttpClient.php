<?php

namespace App\Tests\Double;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class FakeUpstreamHttpClient implements HttpClientInterface
{
    /** @var list<array{method: string, url: string, status: int, body: string}> */
    private static array $queue = [];

    /** @var list<array{method: string, url: string, options: array<string, mixed>}> */
    private static array $calls = [];
    private MockHttpClient $innerClient;

    public function __construct()
    {
        $this->innerClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            self::$calls[] = [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ];

            if (self::$queue === []) {
                throw new TransportException('No queued fake upstream responses.');
            }

            $next = \array_shift(self::$queue);
            \assert($next !== null);
            if ($next['method'] !== $method || $next['url'] !== $url) {
                throw new TransportException(\sprintf(
                    'Unexpected request %s %s, expected %s %s.',
                    $method,
                    $url,
                    $next['method'],
                    $next['url']
                ));
            }

            return new MockResponse($next['body'], ['http_code' => $next['status']]);
        });
    }

    public static function reset(): void
    {
        self::$queue = [];
        self::$calls = [];
    }

    /**
     * @param list<array{method: string, url: string, status?: int, body?: string}> $responses
     */
    public static function queueResponses(array $responses): void
    {
        self::$queue = [];
        foreach ($responses as $response) {
            self::$queue[] = [
                'method' => $response['method'],
                'url' => $response['url'],
                'status' => $response['status'] ?? 200,
                'body' => $response['body'] ?? '{}',
            ];
        }
    }

    /**
     * @return list<array{method: string, url: string, options: array<string, mixed>}>
     */
    public static function calls(): array
    {
        return self::$calls;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->innerClient->request($method, $url, $options);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->innerClient->stream($responses, $timeout);
    }

    public function withOptions(array $options): static
    {
        $this->innerClient = $this->innerClient->withOptions($options);

        return $this;
    }
}
