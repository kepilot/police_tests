<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Topic;
use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\TopicLevel;

interface TopicRepositoryInterface
{
    public function save(Topic $topic): void;
    
    public function findById(TopicId $id): ?Topic;
    
    public function findAll(): array;
    
    public function findByLevel(TopicLevel $level): array;
    
    public function findActive(): array;
    
    public function delete(TopicId $id): void;
    
    public function count(): int;
    
    public function countByLevel(TopicLevel $level): int;
} 