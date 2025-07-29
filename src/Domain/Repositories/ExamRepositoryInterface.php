<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Exam;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;

interface ExamRepositoryInterface
{
    public function save(Exam $exam): void;
    
    public function findById(ExamId $id): ?Exam;
    
    public function findAll(): array;
    
    public function findByTopicId(TopicId $topicId): array;
    
    public function findActive(): array;
    
    public function findActiveByTopicId(TopicId $topicId): array;
    
    public function delete(ExamId $id): void;
    
    public function count(): int;
    
    public function countByTopicId(TopicId $topicId): int;
} 