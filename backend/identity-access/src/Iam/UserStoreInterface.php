<?php

namespace App\Iam;

interface UserStoreInterface
{
    public function createSchema(): void;

    public function emailExists(string $email): bool;

    public function create(string $email, string $passwordHash): string;

    /**
     * @return array{userId: string, passwordHash: string}|null
     */
    public function findByEmail(string $email): ?array;

    /**
     * @return array{userId: string, passwordHash: string}|null
     */
    public function findByUserId(string $userId): ?array;

    public function updatePasswordHash(string $userId, string $passwordHash): bool;
}
