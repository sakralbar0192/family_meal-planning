<?php

namespace App\Iam;

use Predis\Client;
use Symfony\Component\Uid\Uuid;

final class SessionStore implements SessionStoreInterface
{
    public function __construct(
        private readonly Client $redis,
        private readonly int $sessionTtlSeconds,
    ) {
    }

    public function create(string $userId): array
    {
        $sessionId = Uuid::v4()->toRfc4122();
        $createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $expiresAt = $createdAt->modify(\sprintf('+%d seconds', $this->sessionTtlSeconds));

        $payload = \json_encode([
            'userId' => $userId,
            'createdAt' => $createdAt->format(\DateTimeInterface::ATOM),
            'expiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);

        $this->redis->setex($this->key($sessionId), $this->sessionTtlSeconds, $payload);

        return [
            'sessionId' => $sessionId,
            'userId' => $userId,
            'expiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array{userId: string, expiresAt: string}|null
     */
    public function get(string $sessionId): ?array
    {
        $raw = $this->redis->get($this->key($sessionId));
        if (!\is_string($raw) || $raw === '') {
            return null;
        }

        /** @var array{userId?: string, expiresAt?: string} $decoded */
        $decoded = \json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($decoded['userId']) || !\is_string($decoded['userId'])) {
            return null;
        }
        if (!isset($decoded['expiresAt']) || !\is_string($decoded['expiresAt'])) {
            return null;
        }

        return ['userId' => $decoded['userId'], 'expiresAt' => $decoded['expiresAt']];
    }

    public function delete(string $sessionId): bool
    {
        return (int) $this->redis->del([$this->key($sessionId)]) > 0;
    }

    public function ping(): bool
    {
        return \strtoupper((string) $this->redis->ping()) === 'PONG';
    }

    private function key(string $sessionId): string
    {
        return 'iam:session:'.$sessionId;
    }
}
