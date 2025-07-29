<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\ExamAttempt;
use App\Domain\ValueObjects\ExamAttemptId;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;

interface ExamAttemptRepositoryInterface
{
    public function save(ExamAttempt $attempt): void;
    
    public function findById(ExamAttemptId $id): ?ExamAttempt;
    
    public function findAll(): array;
    
    public function findByUserId(UserId $userId): array;
    
    public function findByExamId(ExamId $examId): array;
    
    public function findByUserIdAndExamId(UserId $userId, ExamId $examId): array;
    
    public function findCompletedByUserId(UserId $userId): array;
    
    public function findPassedByUserId(UserId $userId): array;
    
    public function delete(ExamAttemptId $id): void;
    
    public function count(): int;
    
    public function countByUserId(UserId $userId): int;
    
    public function countByExamId(ExamId $examId): int;
    
    public function getAverageScoreByExamId(ExamId $examId): float;
    
    public function getPassRateByExamId(ExamId $examId): float;
} 