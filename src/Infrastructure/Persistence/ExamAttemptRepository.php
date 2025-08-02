<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\ExamAttempt;
use App\Domain\Repositories\ExamAttemptRepositoryInterface;
use App\Domain\ValueObjects\ExamAttemptId;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use PDO;

final class ExamAttemptRepository implements ExamAttemptRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(ExamAttempt $attempt): void
    {
        // Check if attempt already exists
        $existing = $this->findById($attempt->getId());
        
        if ($existing) {
            // Update existing attempt
            $sql = "UPDATE exam_attempts SET 
                    score = :score, 
                    passed = :passed, 
                    completed_at = :completed_at, 
                    deleted_at = :deleted_at
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $attempt->getId()->toString(),
                'score' => $attempt->getScore()->value(),
                'passed' => $attempt->isPassed() ? 1 : 0,
                'completed_at' => $attempt->getCompletedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $attempt->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        } else {
            // Insert new attempt
            $sql = "INSERT INTO exam_attempts (id, user_id, exam_id, score, passed, started_at, completed_at, deleted_at) 
                    VALUES (:id, :user_id, :exam_id, :score, :passed, :started_at, :completed_at, :deleted_at)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $attempt->getId()->toString(),
                'user_id' => $attempt->getUserId()->toString(),
                'exam_id' => $attempt->getExamId()->toString(),
                'score' => $attempt->getScore()->value(),
                'passed' => $attempt->isPassed() ? 1 : 0,
                'started_at' => $attempt->getStartedAt()->format('Y-m-d H:i:s'),
                'completed_at' => $attempt->getCompletedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $attempt->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        }
    }

    public function findById(ExamAttemptId $id): ?ExamAttempt
    {
        $sql = "SELECT * FROM exam_attempts WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE deleted_at IS NULL ORDER BY started_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY started_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findByExamId(ExamId $examId): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE exam_id = :exam_id AND deleted_at IS NULL ORDER BY started_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findByUserIdAndExamId(UserId $userId, ExamId $examId): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE user_id = :user_id AND exam_id = :exam_id AND deleted_at IS NULL ORDER BY started_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId->toString(),
            'exam_id' => $examId->toString()
        ]);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findCompletedByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE user_id = :user_id AND completed_at IS NOT NULL AND deleted_at IS NULL ORDER BY completed_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findPassedByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE user_id = :user_id AND passed = 1 AND deleted_at IS NULL ORDER BY completed_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findCompleted(): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE completed_at IS NOT NULL AND deleted_at IS NULL ORDER BY completed_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function findPassed(): array
    {
        $sql = "SELECT * FROM exam_attempts WHERE passed = 1 AND deleted_at IS NULL ORDER BY completed_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $attempts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $attempts[] = $this->hydrateFromRow($row);
        }

        return $attempts;
    }

    public function delete(ExamAttemptId $id): void
    {
        $sql = "UPDATE exam_attempts SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM exam_attempts WHERE deleted_at IS NULL";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function countByUserId(UserId $userId): int
    {
        $sql = "SELECT COUNT(*) FROM exam_attempts WHERE user_id = :user_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    public function countByExamId(ExamId $examId): int
    {
        $sql = "SELECT COUNT(*) FROM exam_attempts WHERE exam_id = :exam_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    public function getAverageScoreByExamId(ExamId $examId): float
    {
        $sql = "SELECT AVG(score) FROM exam_attempts WHERE exam_id = :exam_id AND completed_at IS NOT NULL AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        return (float) $stmt->fetchColumn() ?: 0.0;
    }

    public function getPassRateByExamId(ExamId $examId): float
    {
        $sql = "SELECT 
                    CASE 
                        WHEN COUNT(*) = 0 THEN 0 
                        ELSE (COUNT(CASE WHEN passed = 1 THEN 1 END) * 100.0 / COUNT(*))
                    END as pass_rate
                FROM exam_attempts 
                WHERE exam_id = :exam_id AND completed_at IS NOT NULL AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        return (float) $stmt->fetchColumn() ?: 0.0;
    }

    public function getAverageScoreByUserId(UserId $userId): float
    {
        $sql = "SELECT AVG(score) FROM exam_attempts WHERE user_id = :user_id AND completed_at IS NOT NULL AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        return (float) $stmt->fetchColumn() ?: 0.0;
    }

    private function hydrateFromRow(array $row): ExamAttempt
    {
        $attempt = new ExamAttempt(
            UserId::fromString($row['user_id']),
            ExamId::fromString($row['exam_id'])
        );

        // Use reflection to set the ID and other properties
        $reflection = new \ReflectionClass($attempt);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($attempt, ExamAttemptId::fromString($row['id']));

        $scoreProperty = $reflection->getProperty('score');
        $scoreProperty->setAccessible(true);
        $scoreProperty->setValue($attempt, new \App\Domain\ValueObjects\ExamScore((int) $row['score']));

        $passedProperty = $reflection->getProperty('passed');
        $passedProperty->setAccessible(true);
        $passedProperty->setValue($attempt, (bool) $row['passed']);

        $startedAtProperty = $reflection->getProperty('startedAt');
        $startedAtProperty->setAccessible(true);
        $startedAtProperty->setValue($attempt, new \DateTimeImmutable($row['started_at']));

        if ($row['completed_at']) {
            $completedAtProperty = $reflection->getProperty('completedAt');
            $completedAtProperty->setAccessible(true);
            $completedAtProperty->setValue($attempt, new \DateTimeImmutable($row['completed_at']));
        }

        if ($row['deleted_at']) {
            $deletedAtProperty = $reflection->getProperty('deletedAt');
            $deletedAtProperty->setAccessible(true);
            $deletedAtProperty->setValue($attempt, new \DateTimeImmutable($row['deleted_at']));
        }

        return $attempt;
    }
} 