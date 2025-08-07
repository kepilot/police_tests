<?php

declare(strict_types=1);

namespace App\Infrastructure\Container;

use App\Application\Commands\CreateUserHandler;
use App\Application\Commands\LoginUserHandler;
use App\Application\Commands\RegisterUserHandler;
use App\Application\Commands\CreateTopicHandler;
use App\Application\Commands\CreateExamHandler;
use App\Application\Commands\CreateQuestionHandler;
use App\Application\Commands\AssignExamHandler;
use App\Application\Commands\AssociateQuestionWithTopicHandler;
use App\Application\Commands\DisassociateQuestionFromTopicHandler;
use App\Application\Commands\SetQuestionTopicsHandler;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Repositories\TopicRepositoryInterface;
use App\Domain\Repositories\ExamRepositoryInterface;
use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\Repositories\ExamAttemptRepositoryInterface;
use App\Domain\Repositories\ExamAssignmentRepositoryInterface;
use App\Domain\Repositories\QuestionTopicRepositoryInterface;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Infrastructure\Persistence\UserRepository;
use App\Infrastructure\Persistence\TopicRepository;
use App\Infrastructure\Persistence\ExamRepository;
use App\Infrastructure\Persistence\QuestionRepository;
use App\Infrastructure\Persistence\ExamAttemptRepository;
use App\Infrastructure\Persistence\ExamAssignmentRepository;
use App\Infrastructure\Persistence\QuestionTopicRepository;
use App\Infrastructure\Services\JwtService;
use App\Infrastructure\Middleware\AuthMiddleware;
use App\Infrastructure\Routing\Router;
use App\Application\Services\QuestionTopicService;
use App\Presentation\Controllers\TopicController;
use App\Presentation\Controllers\ExamController;
use App\Presentation\Controllers\QuestionController;
use App\Presentation\Controllers\ExamAssignmentController;
use App\Presentation\Controllers\ExamAttemptController;
use App\Presentation\Controllers\LearningController;
use App\Presentation\Controllers\PdfUploadController;

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
        $this->services[ExamAssignmentRepositoryInterface::class] = fn() => new ExamAssignmentRepository($this->get(PDO::class));
        $this->services[QuestionTopicRepositoryInterface::class] = fn() => new QuestionTopicRepository($this->get(PDO::class));
        
        // Services
        $this->services[QuestionTopicService::class] = fn() => new QuestionTopicService(
            $this->get(QuestionRepositoryInterface::class),
            $this->get(TopicRepositoryInterface::class),
            $this->get(QuestionTopicRepositoryInterface::class)
        );
        
        // Command handlers
        $this->services[CreateUserHandler::class] = fn() => new CreateUserHandler($this->get(UserRepositoryInterface::class));
        $this->services[RegisterUserHandler::class] = fn() => new RegisterUserHandler($this->get(UserRepositoryInterface::class));
        $this->services[LoginUserHandler::class] = fn() => new LoginUserHandler($this->get(UserRepositoryInterface::class), $this->get(JwtService::class));
        $this->services[CreateTopicHandler::class] = fn() => new CreateTopicHandler($this->get(TopicRepositoryInterface::class));
        $this->services[CreateExamHandler::class] = fn() => new CreateExamHandler($this->get(ExamRepositoryInterface::class), $this->get(TopicRepositoryInterface::class));
        $this->services[CreateQuestionHandler::class] = fn() => new CreateQuestionHandler($this->get(QuestionRepositoryInterface::class));
        $this->services[AssignExamHandler::class] = fn() => new AssignExamHandler($this->get(ExamAssignmentRepositoryInterface::class), $this->get(UserRepositoryInterface::class), $this->get(ExamRepositoryInterface::class));
        $this->services[AssociateQuestionWithTopicHandler::class] = fn() => new AssociateQuestionWithTopicHandler($this->get(QuestionTopicService::class));
        $this->services[DisassociateQuestionFromTopicHandler::class] = fn() => new DisassociateQuestionFromTopicHandler($this->get(QuestionTopicService::class));
        $this->services[SetQuestionTopicsHandler::class] = fn() => new SetQuestionTopicsHandler($this->get(QuestionTopicService::class));
        
        // Controllers
        $this->services[TopicController::class] = fn() => new TopicController($this);
        $this->services[ExamController::class] = fn() => new ExamController($this);
        $this->services[QuestionController::class] = fn() => new QuestionController($this);
        $this->services[ExamAssignmentController::class] = fn() => new ExamAssignmentController($this);
        $this->services[ExamAttemptController::class] = fn() => new ExamAttemptController($this);
        $this->services[LearningController::class] = fn() => new LearningController($this);
        $this->services[PdfUploadController::class] = fn() => new PdfUploadController($this);
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