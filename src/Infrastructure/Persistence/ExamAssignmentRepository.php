<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\ExamAssignment;
use App\Domain\Repositories\ExamAssignmentRepositoryInterface;
use App\Domain\ValueObjects\ExamAssignmentId;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use PDO;

final class ExamAssignmentRepository implements ExamAssignmentRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(ExamAssignment $assignment): void
    {
        // Check if assignment already exists
        $existing = $this->findById($assignment->getId());
        
        if ($existing) {
            // Update existing assignment
            $sql = "UPDATE exam_assignments SET 
                    due_date = :due_date, 
                    is_completed = :is_completed, 
                    completed_at = :completed_at, 
                    deleted_at = :deleted_at
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $assignment->getId()->toString(),
                'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                'is_completed' => $assignment->isCompleted() ? 1 : 0,
                'completed_at' => $assignment->getCompletedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $assignment->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        } else {
            // Insert new assignment
            $sql = "INSERT INTO exam_assignments (id, user_id, exam_id, assigned_by, assigned_at, due_date, is_completed, completed_at, deleted_at) 
                    VALUES (:id, :user_id, :exam_id, :assigned_by, :assigned_at, :due_date, :is_completed, :completed_at, :deleted_at)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $assignment->getId()->toString(),
                'user_id' => $assignment->getUserId()->toString(),
                'exam_id' => $assignment->getExamId()->toString(),
                'assigned_by' => $assignment->getAssignedBy()->toString(),
                'assigned_at' => $assignment->getAssignedAt()->format('Y-m-d H:i:s'),
                'due_date' => $assignment->getDueDate()?->format('Y-m-d H:i:s'),
                'is_completed' => $assignment->isCompleted() ? 1 : 0,
                'completed_at' => $assignment->getCompletedAt()?->format('Y-m-d H:i:s'),
                'deleted_at' => $assignment->getDeletedAt()?->format('Y-m-d H:i:s')
            ]);
        }
    }

    public function findById(ExamAssignmentId $id): ?ExamAssignment
    {
        $sql = "SELECT * FROM exam_assignments WHERE id = :id AND deleted_at IS NULL";
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
        $sql = "SELECT * FROM exam_assignments WHERE deleted_at IS NULL ORDER BY assigned_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function findByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_assignments WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY assigned_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function findByExamId(ExamId $examId): array
    {
        $sql = "SELECT * FROM exam_assignments WHERE exam_id = :exam_id AND deleted_at IS NULL ORDER BY assigned_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function findByUserIdAndExamId(UserId $userId, ExamId $examId): ?ExamAssignment
    {
        $sql = "SELECT * FROM exam_assignments WHERE user_id = :user_id AND exam_id = :exam_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId->toString(),
            'exam_id' => $examId->toString()
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    public function findActiveByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_assignments WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY assigned_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function findCompletedByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_assignments WHERE user_id = :user_id AND is_completed = 1 AND deleted_at IS NULL ORDER BY completed_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function findOverdueByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_assignments 
                WHERE user_id = :user_id 
                AND is_completed = 0 
                AND due_date IS NOT NULL 
                AND due_date < NOW() 
                AND deleted_at IS NULL 
                ORDER BY due_date ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function findPendingByUserId(UserId $userId): array
    {
        $sql = "SELECT * FROM exam_assignments 
                WHERE user_id = :user_id 
                AND is_completed = 0 
                AND deleted_at IS NULL 
                ORDER BY assigned_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        
        $assignments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $assignments[] = $this->hydrateFromRow($row);
        }

        return $assignments;
    }

    public function delete(ExamAssignmentId $id): void
    {
        $sql = "UPDATE exam_assignments SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM exam_assignments WHERE deleted_at IS NULL";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function countByUserId(UserId $userId): int
    {
        $sql = "SELECT COUNT(*) FROM exam_assignments WHERE user_id = :user_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    public function countByExamId(ExamId $examId): int
    {
        $sql = "SELECT COUNT(*) FROM exam_assignments WHERE exam_id = :exam_id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['exam_id' => $examId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    public function countCompletedByUserId(UserId $userId): int
    {
        $sql = "SELECT COUNT(*) FROM exam_assignments WHERE user_id = :user_id AND is_completed = 1 AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    public function countOverdueByUserId(UserId $userId): int
    {
        $sql = "SELECT COUNT(*) FROM exam_assignments 
                WHERE user_id = :user_id 
                AND is_completed = 0 
                AND due_date IS NOT NULL 
                AND due_date < NOW() 
                AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId->toString()]);
        return (int) $stmt->fetchColumn();
    }

    private function hydrateFromRow(array $row): ExamAssignment
    {
        $assignment = new ExamAssignment(
            UserId::fromString($row['user_id']),
            ExamId::fromString($row['exam_id']),
            UserId::fromString($row['assigned_by']),
            $row['due_date'] ? new \DateTimeImmutable($row['due_date']) : null
        );

        // Use reflection to set the ID and other properties
        $reflection = new \ReflectionClass($assignment);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($assignment, ExamAssignmentId::fromString($row['id']));

        $assignedAtProperty = $reflection->getProperty('assignedAt');
        $assignedAtProperty->setAccessible(true);
        $assignedAtProperty->setValue($assignment, new \DateTimeImmutable($row['assigned_at']));

        if ($row['is_completed']) {
            $isCompletedProperty = $reflection->getProperty('isCompleted');
            $isCompletedProperty->setAccessible(true);
            $isCompletedProperty->setValue($assignment, true);

            if ($row['completed_at']) {
                $completedAtProperty = $reflection->getProperty('completedAt');
                $completedAtProperty->setAccessible(true);
                $completedAtProperty->setValue($assignment, new \DateTimeImmutable($row['completed_at']));
            }
        }

        if ($row['deleted_at']) {
            $deletedAtProperty = $reflection->getProperty('deletedAt');
            $deletedAtProperty->setAccessible(true);
            $deletedAtProperty->setValue($assignment, new \DateTimeImmutable($row['deleted_at']));
        }

        return $assignment;
    }
} 