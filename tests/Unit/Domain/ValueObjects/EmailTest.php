<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use Tests\TestCase;
use App\Domain\ValueObjects\Email;
use InvalidArgumentException;

class EmailTest extends TestCase
{
    public function testValidEmailCreation(): void
    {
        $email = new Email('test@example.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertEquals('test@example.com', $email->value());
    }

    public function testEmailWithUppercase(): void
    {
        $email = new Email('TEST@EXAMPLE.COM');

        $this->assertEquals('test@example.com', $email->value());
    }

    public function testEmailWithSpaces(): void
    {
        $email = new Email('  test@example.com  ');

        $this->assertEquals('test@example.com', $email->value());
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        new Email('invalid-email');
    }

    public function testEmptyEmailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        new Email('');
    }

    public function testWhitespaceOnlyEmailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        new Email('   ');
    }

    public function testEmailEquality(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('different@example.com');

        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function testEmailToString(): void
    {
        $email = new Email('test@example.com');

        $this->assertEquals('test@example.com', (string) $email);
    }

    public function testComplexEmailAddresses(): void
    {
        $validEmails = [
            'user+tag@example.com',
            'user.name@example.com',
            'user-name@example.co.uk',
            'user123@example-domain.com',
            'user@example.com',
        ];

        foreach ($validEmails as $emailAddress) {
            $email = new Email($emailAddress);
            $this->assertEquals(strtolower(trim($emailAddress)), $email->value());
        }
    }

    public function testInvalidEmailFormats(): void
    {
        $invalidEmails = [
            'plainaddress',
            '@missingdomain.com',
            'missing@.com',
            'missing@domain',
            'spaces in@email.com',
            'multiple@@at.com',
        ];

        foreach ($invalidEmails as $emailAddress) {
            $this->expectException(InvalidArgumentException::class);
            new Email($emailAddress);
        }
    }
} 