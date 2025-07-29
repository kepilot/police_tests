<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use Tests\TestCase;
use App\Domain\ValueObjects\Password;
use InvalidArgumentException;

class PasswordTest extends TestCase
{
    public function testValidPasswordCreation(): void
    {
        $password = new Password('ValidPass123!');

        $this->assertInstanceOf(Password::class, $password);
        $this->assertNotEmpty($password->getHash());
        $this->assertStringStartsWith('$2y$', $password->getHash());
    }

    public function testPasswordTooShortThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 8 characters long');

        new Password('Short1!');
    }

    public function testPasswordWithoutUppercaseThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one uppercase letter');

        new Password('lowercase123!');
    }

    public function testPasswordWithoutLowercaseThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one lowercase letter');

        new Password('UPPERCASE123!');
    }

    public function testPasswordWithoutNumberThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one number');

        new Password('NoNumbers!');
    }

    public function testPasswordWithoutSpecialCharacterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least one special character');

        new Password('NoSpecialChar123');
    }

    public function testPasswordVerification(): void
    {
        $password = new Password('ValidPass123!');

        $this->assertTrue($password->verify('ValidPass123!'));
        $this->assertFalse($password->verify('WrongPassword'));
        $this->assertFalse($password->verify(''));
    }

    public function testPasswordFromHash(): void
    {
        $originalPassword = new Password('ValidPass123!');
        $hash = $originalPassword->getHash();

        $passwordFromHash = Password::fromHash($hash);

        $this->assertEquals($hash, $passwordFromHash->getHash());
        $this->assertTrue($passwordFromHash->verify('ValidPass123!'));
    }

    public function testPasswordNeedsRehash(): void
    {
        // Set a very low cost for testing
        $_ENV['PASSWORD_HASH_COST'] = '4';
        
        $password = new Password('ValidPass123!');
        
        // Should not need rehash immediately
        $this->assertFalse($password->needsRehash());
        
        // Test with different cost
        $_ENV['PASSWORD_HASH_COST'] = '12';
        $this->assertTrue($password->needsRehash());
    }

    public function testPasswordToString(): void
    {
        $password = new Password('ValidPass123!');
        $hash = $password->getHash();

        $this->assertEquals($hash, (string) $password);
    }

    public function testValidPasswordFormats(): void
    {
        $validPasswords = [
            'ValidPass123!',
            'AnotherPass456@',
            'MySecurePass789#',
            'ComplexPass$123',
            'StrongPass%456',
        ];

        foreach ($validPasswords as $passwordString) {
            $password = new Password($passwordString);
            $this->assertTrue($password->verify($passwordString));
        }
    }

    public function testPasswordHashUniqueness(): void
    {
        $password1 = new Password('ValidPass123!');
        $password2 = new Password('ValidPass123!');

        // Same password should produce different hashes (due to salt)
        $this->assertNotEquals($password1->getHash(), $password2->getHash());
        
        // But both should verify correctly
        $this->assertTrue($password1->verify('ValidPass123!'));
        $this->assertTrue($password2->verify('ValidPass123!'));
    }

    public function testPasswordImmutability(): void
    {
        $password = new Password('ValidPass123!');
        $originalHash = $password->getHash();

        // Verify the password
        $password->verify('ValidPass123!');

        // Hash should remain the same
        $this->assertEquals($originalHash, $password->getHash());
    }
} 