<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Services\JwtService;

final class LoginUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtService $jwtService
    ) {
    }

    public function __invoke(LoginUserCommand $command): array
    {
        $user = $this->userRepository->findByEmail($command->getEmail()->value());
        
        if (!$user) {
            throw new \InvalidArgumentException('Invalid email or password');
        }

        if (!$user->isActive()) {
            throw new \InvalidArgumentException('Account is deactivated');
        }

        if (!$user->verifyPassword($command->getPassword())) {
            throw new \InvalidArgumentException('Invalid email or password');
        }

        // Record login
        $user->recordLogin();
        $this->userRepository->save($user);

        // Generate JWT token
        $token = $this->jwtService->generateToken($user);

        return [
            'user' => [
                'id' => $user->getId()->toString(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->value(),
                'lastLoginAt' => $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
            ],
            'token' => $token,
            'expiresIn' => 3600 // 1 hour
        ];
    }
} 