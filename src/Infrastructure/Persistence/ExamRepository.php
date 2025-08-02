<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Exam;
use App\Domain\Repositories\ExamRepositoryInterface;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use PDO;

final class ExamRepository implements ExamRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Exam $exam): void
    {
        // Check if exam exists
        $existingExam = $this->findById($exam->getId());
        
        if ($existingExam) {
            // Update existing exam
            $sql = "UPDATE exams SET 
                    title = :title, 
                    description = :description, 
                    duration_minutes = :duration_minutes, 
                    passing_score_percentage = :passing_score_percentage, 
                    topic_id = :topic_id, 
                    is_active = :is_active, 
                    updated_at = :updated_at, 
                    deleted_at = :deleted_at
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $exam->getId()->toString(),
                'title' => $exam->getTitle()->value(),
                'description' => $exam->getDescription()->value(),
                'duration_minutes' => $exam->getDuration()->value(),
                'passing_score_percentage' => $exam->getPassingScore()->value(),
                'topic_id' => $exam->getTopicId()->toString(),
                'is_active' => $exam->isActive(),
                'updated_at' => $exam->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $exam->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        } else {
            // Insert new exam
            $sql = "INSERT INTO exams (id, title, description, duration_minutes, passing_score_percentage, topic_id, is_active, created_at, updated_at, deleted_at) 
                    VALUES (:id, :title, :description, :duration_minutes, :passing_score_percentage, :topic_id, :is_active, :created_at, :updated_at, :deleted_at)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $exam->getId()->toString(),
                'title' => $exam->getTitle()->value(),
                'description' => $exam->getDescription()->value(),
                'duration_minutes' => $exam->getDuration()->value(),
                'passing_score_percentage' => $exam->getPassingScore()->value(),
                'topic_id' => $exam->getTopicId()->toString(),
                'is_active' => $exam->isActive(),
                'created_at' => $exam->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => $exam->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $exam->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        }
    }

    public function findById(ExamId $id): ?Exam
    {
        $sql = "SELECT * FROM exams WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return $this->createExamFromRow($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM exams WHERE deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $exams = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exams[] = $this->createExamFromRow($row);
        }

        return $exams;
    }

    public function findByTopicId(TopicId $topicId): array
    {
        $sql = "SELECT * FROM exams WHERE topic_id = :topic_id AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        
        $exams = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exams[] = $this->createExamFromRow($row);
        }

        return $exams;
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM exams WHERE is_active = 1 AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $exams = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exams[] = $this->createExamFromRow($row);
        }

        return $exams;
    }

    public function findActiveByTopicId(TopicId $topicId): array
    {
        $sql = "SELECT * FROM exams WHERE topic_id = :topic_id AND is_active = 1 AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        
        $exams = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $exams[] = $this->createExamFromRow($row);
        }

        return $exams;
    }

    public function delete(ExamId $id): void
    {
        $sql = "UPDATE exams SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM exams WHERE deleted_at IS NULL";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function countByTopicId(TopicId $topicId): int
    {
        $sql = "SELECT COUNT(*) FROM exams WHERE topic_id = :topic_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    private function createExamFromRow(array $row): Exam
    {
        $exam = new Exam(
            new ExamTitle($row['title']),
            new ExamDescription($row['description']),
            new ExamDuration($row['duration_minutes']),
            new ExamPassingScore($row['passing_score_percentage']),
            TopicId::fromString($row['topic_id'])
        );

        // Use reflection to set the ID and other properties
        $reflection = new \ReflectionClass($exam);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($exam, ExamId::fromString($row['id']));

        $isActiveProperty = $reflection->getProperty('isActive');
        $isActiveProperty->setAccessible(true);
        $isActiveProperty->setValue($exam, (bool) $row['is_active']);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($exam, new \DateTimeImmutable($row['created_at']));

        if ($row['updated_at']) {
            $updatedAtProperty = $reflection->getProperty('updatedAt');
            $updatedAtProperty->setAccessible(true);
            $updatedAtProperty->setValue($exam, new \DateTimeImmutable($row['updated_at']));
        }

        if ($row['deleted_at']) {
            $deletedAtProperty = $reflection->getProperty('deletedAt');
            $deletedAtProperty->setAccessible(true);
            $deletedAtProperty->setValue($exam, new \DateTimeImmutable($row['deleted_at']));
        }

        return $exam;
    }
} 