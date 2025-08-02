<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use DateTimeImmutable;

final class AssignExamCommand
{
    public function __construct(
        private readonly UserId $userId,
        private readonly ExamId $examId,
        private readonly UserId $assignedBy,
        private readonly ?DateTimeImmutable $dueDate = null
    ) {
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

    public function getDueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }
} 