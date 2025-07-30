<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Question;
use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;
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

    public function findByTopicId(TopicId $topicId): array
    {
        $sql = "SELECT q.* FROM questions q
                INNER JOIN question_topics qt ON q.id = qt.question_id
                WHERE qt.topic_id = :topic_id AND q.deleted_at IS NULL
                ORDER BY q.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = $this->createQuestionFromRow($row);
        }

        return $questions;
    }

    public function findActiveByTopicId(TopicId $topicId): array
    {
        $sql = "SELECT q.* FROM questions q
                INNER JOIN question_topics qt ON q.id = qt.question_id
                WHERE qt.topic_id = :topic_id AND q.is_active = 1 AND q.deleted_at IS NULL
                ORDER BY q.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = $this->createQuestionFromRow($row);
        }

        return $questions;
    }

    private function createQuestionFromRow(array $row): Question
    {
        // TODO: Implement createQuestionFromRow method
        // This would create a Question entity from database row
        // For now, returning null as placeholder
        return null;
    }
} 