<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(RegisterUserCommand $command): User
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findByEmail($command->getEmail()->value());
        if ($existingUser) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        $user = new User(
            $command->getName(),
            $command->getEmail(),
            $command->getPassword()->getHash()
        );

        $this->userRepository->save($user);

        return $user;
    }
} 