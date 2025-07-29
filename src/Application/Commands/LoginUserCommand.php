<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\Email;

final class LoginUserCommand
{
    public function __construct(
        private readonly Email $email,
        private readonly string $password
    ) {
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
} 