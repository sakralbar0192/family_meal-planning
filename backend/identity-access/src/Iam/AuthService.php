<?php

namespace App\Iam;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class AuthService
{
    public function __construct(
        private readonly UserStoreInterface $users,
        private readonly SessionStoreInterface $sessions,
    ) {
    }

    public function register(string $email, string $password): string
    {
        $this->validateEmailPassword($email, $password);
        if ($this->users->emailExists($email)) {
            throw new ConflictHttpException('Email already exists.');
        }

        return $this->users->create($email, \password_hash($password, PASSWORD_DEFAULT));
    }

    /**
     * @return array{sessionId: string, userId: string, expiresAt: string}
     */
    public function createSession(string $email, string $password): array
    {
        $this->validateEmailPassword($email, $password);
        $user = $this->users->findByEmail($email);
        if ($user === null || !\password_verify($password, $user['passwordHash'])) {
            throw new UnauthorizedHttpException('', 'Invalid credentials.');
        }

        return $this->sessions->create($user['userId']);
    }

    /**
     * @return array{userId: string}
     */
    public function validateSession(string $sessionId): array
    {
        $session = $this->sessions->get($sessionId);
        if ($session === null) {
            throw new NotFoundHttpException('Session not found.');
        }

        return ['userId' => $session['userId']];
    }

    public function deleteSession(string $sessionId): bool
    {
        return $this->sessions->delete($sessionId);
    }

    public function changePassword(string $userId, string $currentPassword, string $newPassword): void
    {
        $this->validateNewPassword($newPassword);
        $row = $this->users->findByUserId($userId);
        if ($row === null) {
            throw new UnauthorizedHttpException('', 'User not found.');
        }
        if (!\password_verify($currentPassword, $row['passwordHash'])) {
            throw new BadRequestHttpException('Invalid current password.');
        }
        $this->users->updatePasswordHash($userId, \password_hash($newPassword, PASSWORD_DEFAULT));
    }

    private function validateEmailPassword(string $email, string $password): void
    {
        if (!\filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Invalid email format.');
        }
        $this->validateNewPassword($password);
    }

    private function validateNewPassword(string $password): void
    {
        if (\mb_strlen($password) < 8) {
            throw new BadRequestHttpException('Password must be at least 8 characters.');
        }
    }
}
