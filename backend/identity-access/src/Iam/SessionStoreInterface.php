<?php

namespace App\Iam;

interface SessionStoreInterface
{
    /**
     * @return array{sessionId: string, userId: string, expiresAt: string}
     */
    public function create(string $userId): array;

    /**
     * @return array{userId: string, expiresAt: string}|null
     */
    public function get(string $sessionId): ?array;

    public function delete(string $sessionId): bool;
}
