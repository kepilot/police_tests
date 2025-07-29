<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Email;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class User
{
    private UuidInterface $id;
    private string $name;
    private Email $email;
    private string $passwordHash;
    private string $role;
    private bool $isActive;
    private ?\DateTimeImmutable $lastLoginAt;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        Email $email,
        string $passwordHash,
        string $role = 'user',
        ?UuidInterface $id = null
    ) {
        $this->id = $id ?? Uuid::uuid4();
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->isActive = true;
        $this->lastLoginAt = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->role === 'superadmin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function changeName(string $newName): void
    {
        $this->name = $newName;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changePassword(string $newPasswordHash): void
    {
        $this->passwordHash = $newPasswordHash;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeRole(string $newRole): void
    {
        $validRoles = ['user', 'admin', 'superadmin'];
        if (!in_array($newRole, $validRoles, true)) {
            throw new \InvalidArgumentException('Invalid role. Must be one of: ' . implode(', ', $validRoles));
        }
        
        $this->role = $newRole;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }
} 