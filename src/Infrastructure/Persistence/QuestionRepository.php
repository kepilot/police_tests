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
        // Check if question exists
        $existingQuestion = $this->findById($question->getId());
        
        if ($existingQuestion) {
            // Update existing question
            $sql = "UPDATE questions SET 
                    text = :text, 
                    type = :type, 
                    exam_id = :exam_id, 
                    options = :options, 
                    correct_option = :correct_option, 
                    points = :points, 
                    is_active = :is_active, 
                    updated_at = :updated_at, 
                    deleted_at = :deleted_at
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $question->getId()->toString(),
                'text' => $question->getText()->value(),
                'type' => $question->getType()->value(),
                'exam_id' => $question->getExamId()->toString(),
                'options' => json_encode($question->getOptions()),
                'correct_option' => $question->getCorrectOption(),
                'points' => $question->getPoints(),
                'is_active' => $question->isActive(),
                'updated_at' => $question->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $question->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        } else {
            // Insert new question
            $sql = "INSERT INTO questions (id, text, type, exam_id, options, correct_option, points, is_active, created_at, updated_at, deleted_at) 
                    VALUES (:id, :text, :type, :exam_id, :options, :correct_option, :points, :is_active, :created_at, :updated_at, :deleted_at)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $question->getId()->toString(),
                'text' => $question->getText()->value(),
                'type' => $question->getType()->value(),
                'exam_id' => $question->getExamId()->toString(),
                'options' => json_encode($question->getOptions()),
                'correct_option' => $question->getCorrectOption(),
                'points' => $question->getPoints(),
                'is_active' => $question->isActive(),
                'created_at' => $question->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $question->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $question->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        }
    }

    public function findById(QuestionId $id): ?Question
    {
        $sql = "SELECT * FROM questions WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return $this->createQuestionFromRow($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM questions WHERE deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = $this->createQuestionFromRow($row);
        }

        return $questions;
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM questions WHERE is_active = 1 AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = $this->createQuestionFromRow($row);
        }

        return $questions;
    }

    public function findByExamId(ExamId $examId): array
    {
        $sql = "SELECT * FROM questions WHERE exam_id = :exam_id AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = $this->createQuestionFromRow($row);
        }

        return $questions;
    }

    public function findActiveByExamId(ExamId $examId): array
    {
        $sql = "SELECT * FROM questions WHERE exam_id = :exam_id AND is_active = 1 AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        
        $questions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questions[] = $this->createQuestionFromRow($row);
        }

        return $questions;
    }

    public function delete(QuestionId $id): void
    {
        $sql = "UPDATE questions SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM questions WHERE deleted_at IS NULL";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function countByExamId(ExamId $examId): int
    {
        $sql = "SELECT COUNT(*) FROM questions WHERE exam_id = :exam_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    public function getTotalPointsByExamId(ExamId $examId): int
    {
        $sql = "SELECT SUM(points) FROM questions WHERE exam_id = :exam_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        return (int) $stmt->fetchColumn();
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
        $question = new Question(
            new \App\Domain\ValueObjects\QuestionText($row['text']),
            new \App\Domain\ValueObjects\QuestionType($row['type']),
            \App\Domain\ValueObjects\ExamId::fromString($row['exam_id']),
            json_decode($row['options'], true),
            (int) $row['correct_option'],
            (int) $row['points']
        );

        // Set the ID using reflection since it's private
        $reflection = new \ReflectionClass($question);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($question, \App\Domain\ValueObjects\QuestionId::fromString($row['id']));

        // Set timestamps
        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($question, new \DateTimeImmutable($row['created_at']));

        if ($row['updated_at']) {
            $updatedAtProperty = $reflection->getProperty('updatedAt');
            $updatedAtProperty->setAccessible(true);
            $updatedAtProperty->setValue($question, new \DateTimeImmutable($row['updated_at']));
        }

        if ($row['deleted_at']) {
            $deletedAtProperty = $reflection->getProperty('deletedAt');
            $deletedAtProperty->setAccessible(true);
            $deletedAtProperty->setValue($question, new \DateTimeImmutable($row['deleted_at']));
        }

        // Set is_active
        $isActiveProperty = $reflection->getProperty('isActive');
        $isActiveProperty->setAccessible(true);
        $isActiveProperty->setValue($question, (bool) $row['is_active']);

        // Load topic associations
        $topicIds = $this->loadTopicIdsForQuestion($row['id']);
        $topicIdsProperty = $reflection->getProperty('topicIds');
        $topicIdsProperty->setAccessible(true);
        $topicIdsProperty->setValue($question, $topicIds);

        return $question;
    }

    private function loadTopicIdsForQuestion(string $questionId): array
    {
        $sql = "SELECT topic_id FROM question_topics WHERE question_id = :question_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['question_id' => $questionId]);
        
        $topicIds = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $topicIds[] = $row['topic_id'];
        }
        
        return $topicIds;
    }
} 