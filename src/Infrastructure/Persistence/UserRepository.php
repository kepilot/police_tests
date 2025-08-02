<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\ValueObjects\Email;
use PDO;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function save(User $user): void
    {
        // Check if user exists
        $existingUser = $this->findById($user->getId());
        
        if ($existingUser) {
            // Update existing user
            $sql = "UPDATE users SET 
                    name = :name, 
                    email = :email, 
                    password_hash = :password_hash,
                    role = :role,
                    is_active = :is_active,
                    last_login_at = :last_login_at,
                    updated_at = :updated_at
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $user->getId()->toString(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->value(),
                'password_hash' => $user->getPasswordHash(),
                'role' => $user->getRole(),
                'is_active' => $user->isActive(),
                'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        } else {
            // Insert new user
            $sql = "INSERT INTO users (id, name, email, password_hash, role, is_active, last_login_at, created_at, updated_at) 
                    VALUES (:id, :name, :email, :password_hash, :role, :is_active, :last_login_at, :created_at, :updated_at)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $user->getId()->toString(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->value(),
                'password_hash' => $user->getPasswordHash(),
                'role' => $user->getRole(),
                'is_active' => $user->isActive(),
                'last_login_at' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function findById(UuidInterface $id): ?User
    {
        $sql = "SELECT id, name, email, password_hash, role, is_active, last_login_at, created_at, updated_at FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return $this->createUserFromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT id, name, email, password_hash, role, is_active, last_login_at, created_at, updated_at FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return $this->createUserFromRow($row);
    }

    public function delete(User $user): void
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $user->getId()->toString()]);
    }

    public function findAll(): array
    {
        $sql = "SELECT id, name, email, password_hash, role, is_active, last_login_at, created_at, updated_at FROM users ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->createUserFromRow($row);
        }

        return $users;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM users";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    private function createUserFromRow(array $row): User
    {
        $user = new User(
            $row['name'],
            new Email($row['email']),
            $row['password_hash'],
            $row['role'] ?? 'user',
            Uuid::fromString($row['id'])
        );

        // Use reflection to set the private properties
        $reflection = new \ReflectionClass($user);
        
        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($user, new \DateTimeImmutable($row['created_at']));
        
        if ($row['updated_at']) {
            $updatedAtProperty = $reflection->getProperty('updatedAt');
            $updatedAtProperty->setAccessible(true);
            $updatedAtProperty->setValue($user, new \DateTimeImmutable($row['updated_at']));
        }

        if ($row['last_login_at']) {
            $lastLoginAtProperty = $reflection->getProperty('lastLoginAt');
            $lastLoginAtProperty->setAccessible(true);
            $lastLoginAtProperty->setValue($user, new \DateTimeImmutable($row['last_login_at']));
        }

        $isActiveProperty = $reflection->getProperty('isActive');
        $isActiveProperty->setAccessible(true);
        $isActiveProperty->setValue($user, (bool) $row['is_active']);

        return $user;
    }
} 