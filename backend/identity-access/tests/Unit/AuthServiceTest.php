<?php

namespace App\Tests\Unit;

use App\Iam\AuthService;
use App\Iam\SessionStoreInterface;
use App\Iam\UserStoreInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class AuthServiceTest extends TestCase
{
    public function testRegisterHashesPasswordAndCreatesUser(): void
    {
        $users = new InMemoryUserStore();
        $sessions = new InMemorySessionStore();
        $service = new AuthService($users, $sessions);

        $userId = $service->register('user@example.com', 'secret123');

        self::assertNotSame('', $userId);
        $stored = $users->findByEmail('user@example.com');
        self::assertNotNull($stored);
        self::assertNotSame('secret123', $stored['passwordHash']);
    }

    public function testRegisterFailsForDuplicateEmail(): void
    {
        $users = new InMemoryUserStore();
        $sessions = new InMemorySessionStore();
        $service = new AuthService($users, $sessions);
        $service->register('user@example.com', 'secret123');

        $this->expectException(ConflictHttpException::class);
        $service->register('user@example.com', 'secret123');
    }

    public function testCreateSessionFailsWithInvalidPassword(): void
    {
        $users = new InMemoryUserStore();
        $sessions = new InMemorySessionStore();
        $service = new AuthService($users, $sessions);
        $service->register('user@example.com', 'secret123');

        $this->expectException(UnauthorizedHttpException::class);
        $service->createSession('user@example.com', 'badpass123');
    }
}

final class InMemoryUserStore implements UserStoreInterface
{
    /** @var array<string, array{userId: string, passwordHash: string}> */
    private array $users = [];

    public function createSchema(): void
    {
    }

    public function emailExists(string $email): bool
    {
        return isset($this->users[\mb_strtolower($email)]);
    }

    public function create(string $email, string $passwordHash): string
    {
        $userId = 'user-'.\count($this->users);
        $this->users[\mb_strtolower($email)] = ['userId' => $userId, 'passwordHash' => $passwordHash];

        return $userId;
    }

    public function findByEmail(string $email): ?array
    {
        return $this->users[\mb_strtolower($email)] ?? null;
    }
}

final class InMemorySessionStore implements SessionStoreInterface
{
    /** @var array<string, array{sessionId: string, userId: string, expiresAt: string}> */
    private array $sessions = [];

    public function create(string $userId): array
    {
        $session = [
            'sessionId' => 'sess-'.\count($this->sessions),
            'userId' => $userId,
            'expiresAt' => '2030-01-01T00:00:00+00:00',
        ];
        $this->sessions[$session['sessionId']] = $session;

        return $session;
    }

    public function get(string $sessionId): ?array
    {
        $session = $this->sessions[$sessionId] ?? null;
        if ($session === null) {
            return null;
        }

        return ['userId' => $session['userId'], 'expiresAt' => $session['expiresAt']];
    }

    public function delete(string $sessionId): bool
    {
        if (!isset($this->sessions[$sessionId])) {
            return false;
        }
        unset($this->sessions[$sessionId]);

        return true;
    }
}
