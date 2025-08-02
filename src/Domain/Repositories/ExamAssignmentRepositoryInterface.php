<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ExamAssignment;
use App\Domain\ValueObjects\ExamAssignmentId;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;

interface ExamAssignmentRepositoryInterface
{
    public function save(ExamAssignment $assignment): void;

    public function findById(ExamAssignmentId $id): ?ExamAssignment;

    public function findAll(): array;

    public function findByUserId(UserId $userId): array;

    public function findByExamId(ExamId $examId): array;

    public function findByUserIdAndExamId(UserId $userId, ExamId $examId): ?ExamAssignment;

    public function findActiveByUserId(UserId $userId): array;

    public function findCompletedByUserId(UserId $userId): array;

    public function findOverdueByUserId(UserId $userId): array;

    public function findPendingByUserId(UserId $userId): array;

    public function delete(ExamAssignmentId $id): void;

    public function count(): int;

    public function countByUserId(UserId $userId): int;

    public function countByExamId(ExamId $examId): int;

    public function countCompletedByUserId(UserId $userId): int;

    public function countOverdueByUserId(UserId $userId): int;
} 