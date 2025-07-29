<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class Password
{
    private string $value;
    private string $hash;

    public function __construct(string $password)
    {
        $this->validate($password);
        $this->value = $password;
        $this->hash = $this->hashPassword($password);
    }

    public static function fromHash(string $hash): self
    {
        $instance = new self('dummy'); // We'll override the hash
        $instance->hash = $hash;
        return $instance;
    }

    private function validate(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one uppercase letter');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one lowercase letter');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one number');
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new \InvalidArgumentException('Password must contain at least one special character');
        }
    }

    private function hashPassword(string $password): string
    {
        $cost = (int) ($_ENV['PASSWORD_HASH_COST'] ?? 12);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    public function verify(string $password): bool
    {
        return password_verify($password, $this->hash);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function needsRehash(): bool
    {
        $cost = (int) ($_ENV['PASSWORD_HASH_COST'] ?? 12);
        return password_needs_rehash($this->hash, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    public function __toString(): string
    {
        return $this->hash;
    }
} 