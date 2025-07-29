<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Question;
use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\ExamId;
use PDO;

final class QuestionRepository implements QuestionRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Question $question): void
    {
        // TODO: Implement save method
    }

    public function findById(QuestionId $id): ?Question
    {
        // TODO: Implement findById method
        return null;
    }

    public function findAll(): array
    {
        // TODO: Implement findAll method
        return [];
    }

    public function findByExamId(ExamId $examId): array
    {
        // TODO: Implement findByExamId method
        return [];
    }

    public function findActiveByExamId(ExamId $examId): array
    {
        // TODO: Implement findActiveByExamId method
        return [];
    }

    public function delete(QuestionId $id): void
    {
        // TODO: Implement delete method
    }

    public function count(): int
    {
        // TODO: Implement count method
        return 0;
    }

    public function countByExamId(ExamId $examId): int
    {
        // TODO: Implement countByExamId method
        return 0;
    }

    public function getTotalPointsByExamId(ExamId $examId): int
    {
        // TODO: Implement getTotalPointsByExamId method
        return 0;
    }
} 