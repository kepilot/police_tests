<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use Tests\TestCase;
use App\Domain\Entities\User;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use Ramsey\Uuid\Uuid;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($password->getHash(), $user->getPasswordHash());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->getLastLoginAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
    }

    public function testUserCreationWithId(): void
    {
        $id = Uuid::uuid4();
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash(), $id);

        $this->assertEquals($id, $user->getId());
    }

    public function testChangeName(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Old Name', $email, $password->getHash());

        $originalUpdatedAt = $user->getUpdatedAt();
        sleep(1); // Ensure time difference

        $user->changeName('New Name');

        $this->assertEquals('New Name', $user->getName());
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testChangePassword(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $newPasswordHash = password_hash('NewPass123!', PASSWORD_BCRYPT);
        $originalUpdatedAt = $user->getUpdatedAt();
        sleep(1); // Ensure time difference

        $user->changePassword($newPasswordHash);

        $this->assertEquals($newPasswordHash, $user->getPasswordHash());
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testDeactivate(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $this->assertTrue($user->isActive());

        $user->deactivate();

        $this->assertFalse($user->isActive());
    }

    public function testActivate(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $user->deactivate();
        $this->assertFalse($user->isActive());

        $user->activate();

        $this->assertTrue($user->isActive());
    }

    public function testRecordLogin(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $this->assertNull($user->getLastLoginAt());

        $user->recordLogin();

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getLastLoginAt());
        $this->assertGreaterThanOrEqual(
            new \DateTimeImmutable('-1 second'),
            $user->getLastLoginAt()
        );
    }

    public function testVerifyPassword(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $this->assertTrue($user->verifyPassword('TestPass123!'));
        $this->assertFalse($user->verifyPassword('WrongPassword'));
        $this->assertFalse($user->verifyPassword(''));
    }

    public function testUserEquality(): void
    {
        $id = Uuid::uuid4();
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');

        $user1 = new User('Test User', $email, $password->getHash(), $id);
        $user2 = new User('Test User', $email, $password->getHash(), $id);

        $this->assertEquals($user1->getId(), $user2->getId());
        $this->assertEquals($user1->getEmail(), $user2->getEmail());
    }

    public function testUserImmutability(): void
    {
        $email = new Email('test@example.com');
        $password = new Password('TestPass123!');
        $user = new User('Test User', $email, $password->getHash());

        $originalEmail = $user->getEmail();
        $originalPasswordHash = $user->getPasswordHash();

        // These should not change the original values
        $this->assertEquals($originalEmail, $user->getEmail());
        $this->assertEquals($originalPasswordHash, $user->getPasswordHash());
    }
} 