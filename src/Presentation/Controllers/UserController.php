<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Commands\CreateUserCommand;
use App\Application\Commands\LoginUserCommand;
use App\Application\Commands\RegisterUserCommand;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use App\Infrastructure\Container\Container;

final class UserController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function registerUser(string $name, string $email, string $password): array
    {
        try {
            $command = new RegisterUserCommand($name, new Email($email), new Password($password));
            $handler = $this->container->get(\App\Application\Commands\RegisterUserHandler::class);
            
            $user = $handler($command);

            return [
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'id' => $user->getId()->toString(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()->value(),
                    'createdAt' => $user->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => 'Invalid data: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error registering user: ' . $e->getMessage()
            ];
        }
    }

    public function loginUser(string $email, string $password): array
    {
        try {
            $command = new LoginUserCommand(new Email($email), $password);
            $handler = $this->container->get(\App\Application\Commands\LoginUserHandler::class);
            
            $result = $handler($command);

            return [
                'success' => true,
                'message' => 'Login successful',
                'data' => $result
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => 'Invalid credentials: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error during login: ' . $e->getMessage()
            ];
        }
    }

    public function createUser(string $name, string $email, string $password = 'defaultPassword123!'): array
    {
        try {
            $command = new CreateUserCommand($name, new Email($email), new Password($password));
            $handler = $this->container->get(\App\Application\Commands\CreateUserHandler::class);
            
            $user = $handler($command);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->getId()->toString(),
                    'name' => $name,
                    'email' => $email
                ]
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => 'Invalid data: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating user: ' . $e->getMessage()
            ];
        }
    }

    public function listUsers(): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
            $users = $repository->findAll();

            $usersData = [];
            foreach ($users as $user) {
                $usersData[] = [
                    'id' => $user->getId()->toString(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()->value(),
                    'role' => $user->getRole(),
                    'is_active' => $user->isActive(),
                    'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'data' => $usersData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error listing users: ' . $e->getMessage()
            ];
        }
    }
} 