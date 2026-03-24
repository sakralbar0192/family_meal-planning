<?php

namespace App\Iam;

use PDO;
use Symfony\Component\Uid\Uuid;

final class UserRepository implements UserStoreInterface
{
    public function __construct(private readonly Database $database)
    {
    }

    public function createSchema(): void
    {
        $this->database->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS iam_users (
                user_id UUID PRIMARY KEY,
                email VARCHAR(320) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL
            )'
        );
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->database->pdo()->prepare('SELECT 1 FROM iam_users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => \mb_strtolower($email)]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(string $email, string $passwordHash): string
    {
        $userId = Uuid::v4()->toRfc4122();
        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO iam_users (user_id, email, password_hash, created_at)
             VALUES (:userId, :email, :passwordHash, NOW())'
        );
        $stmt->execute([
            'userId' => $userId,
            'email' => \mb_strtolower($email),
            'passwordHash' => $passwordHash,
        ]);

        return $userId;
    }

    /**
     * @return array{userId: string, passwordHash: string}|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT user_id, password_hash FROM iam_users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => \mb_strtolower($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!\is_array($row)) {
            return null;
        }

        return [
            'userId' => (string) $row['user_id'],
            'passwordHash' => (string) $row['password_hash'],
        ];
    }
}
