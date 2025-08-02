<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ExamAssignmentId;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use DateTimeImmutable;

final class ExamAssignment
{
    private ExamAssignmentId $id;
    private UserId $userId;
    private ExamId $examId;
    private UserId $assignedBy;
    private DateTimeImmutable $assignedAt;
    private ?DateTimeImmutable $dueDate;
    private bool $isCompleted;
    private ?DateTimeImmutable $completedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        UserId $userId,
        ExamId $examId,
        UserId $assignedBy,
        ?DateTimeImmutable $dueDate = null
    ) {
        $this->id = ExamAssignmentId::generate();
        $this->userId = $userId;
        $this->examId = $examId;
        $this->assignedBy = $assignedBy;
        $this->assignedAt = new DateTimeImmutable();
        $this->dueDate = $dueDate;
        $this->isCompleted = false;
        $this->completedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): ExamAssignmentId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getExamId(): ExamId
    {
        return $this->examId;
    }

    public function getAssignedBy(): UserId
    {
        return $this->assignedBy;
    }

    public function getAssignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function markAsCompleted(): void
    {
        $this->isCompleted = true;
        $this->completedAt = new DateTimeImmutable();
    }

    public function updateDueDate(?DateTimeImmutable $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null) {
            return false;
        }

        return $this->dueDate < new DateTimeImmutable() && !$this->isCompleted;
    }
} 