<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Question;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;

interface QuestionRepositoryInterface
{
    public function save(Question $question): void;
    
    public function findById(QuestionId $id): ?Question;
    
    public function findAll(): array;
    
    public function findByExamId(ExamId $examId): array;
    
    public function findActiveByExamId(ExamId $examId): array;
    
    public function delete(QuestionId $id): void;
    
    public function count(): int;
    
    public function countByExamId(ExamId $examId): int;
    
    public function getTotalPointsByExamId(ExamId $examId): int;
    
    public function findByTopicId(TopicId $topicId): array;
    
    public function findActiveByTopicId(TopicId $topicId): array;
} 