<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;

final class RegisterUserCommand
{
    public function __construct(
        private readonly string $name,
        private readonly Email $email,
        private readonly Password $password
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): Password
    {
        return $this->password;
    }
} 