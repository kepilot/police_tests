<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use App\Application\Commands\CreateUserHandler;
use App\Application\Commands\LoginUserHandler;
use App\Application\Commands\RegisterUserHandler;
use App\Application\Commands\CreateTopicHandler;
use App\Application\Commands\CreateExamHandler;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\TopicRepositoryInterface;
use App\Domain\Repositories\ExamRepositoryInterface;
use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\Repositories\ExamAttemptRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Persistence\TopicRepository;
use App\Infrastructure\Persistence\ExamRepository;
use App\Infrastructure\Persistence\QuestionRepository;
use App\Infrastructure\Persistence\ExamAttemptRepository;
use App\Infrastructure\Services\JwtService;
use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Routing\Router;

final class Container
{
    private array $services = [];

    public function __construct()
    {
        $this->registerServices();
    }

    private function registerServices(): void
    {
        // Database connection
        $this->services[DatabaseConnection::class] = fn() => new DatabaseConnection();
        
        // PDO instance
        $this->services[PDO::class] = fn() => $this->get(DatabaseConnection::class)->getConnection();
        
        // Services
        $this->services[JwtService::class] = fn() => new JwtService();
        
        // Middleware
        $this->services[AuthMiddleware::class] = fn() => new AuthMiddleware($this);
        
        // Router
        $this->services[Router::class] = fn() => new Router($this, $this->get(AuthMiddleware::class));
        
        // Repositories
        $this->services[UserRepositoryInterface::class] = fn() => new UserRepository($this->get(PDO::class));
        $this->services[TopicRepositoryInterface::class] = fn() => new TopicRepository($this->get(PDO::class));
        $this->services[ExamRepositoryInterface::class] = fn() => new ExamRepository($this->get(PDO::class));
        $this->services[QuestionRepositoryInterface::class] = fn() => new QuestionRepository($this->get(PDO::class));
        $this->services[ExamAttemptRepositoryInterface::class] = fn() => new ExamAttemptRepository($this->get(PDO::class));
        
        // Command handlers
        $this->services[CreateUserHandler::class] = fn() => new CreateUserHandler($this->get(UserRepositoryInterface::class));
        $this->services[RegisterUserHandler::class] = fn() => new RegisterUserHandler($this->get(UserRepositoryInterface::class));
        $this->services[LoginUserHandler::class] = fn() => new LoginUserHandler($this->get(UserRepositoryInterface::class), $this->get(JwtService::class));
        $this->services[CreateTopicHandler::class] = fn() => new CreateTopicHandler($this->get(TopicRepositoryInterface::class));
        $this->services[CreateExamHandler::class] = fn() => new CreateExamHandler($this->get(ExamRepositoryInterface::class), $this->get(TopicRepositoryInterface::class));
    }

    public function get(string $id): object
    {
        if (!isset($this->services[$id])) {
            throw new \InvalidArgumentException("Service '$id' not found");
        }

        if (is_callable($this->services[$id])) {
            $this->services[$id] = $this->services[$id]();
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
} 