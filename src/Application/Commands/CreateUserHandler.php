<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(CreateUserCommand $command): User
    {
        $user = new User(
            $command->getName(),
            $command->getEmail(),
            $command->getPassword()->getHash(),
            $command->getRole()
        );

        $this->userRepository->save($user);
        
        return $user;
    }
} 