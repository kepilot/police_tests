<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\QuestionTopic;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;

interface QuestionTopicRepositoryInterface
{
    public function save(QuestionTopic $questionTopic): void;
    
    public function findByQuestionId(QuestionId $questionId): array;
    
    public function findByTopicId(TopicId $topicId): array;
    
    public function findByQuestionIdAndTopicId(QuestionId $questionId, TopicId $topicId): ?QuestionTopic;
    
    public function deleteByQuestionId(QuestionId $questionId): void;
    
    public function deleteByTopicId(TopicId $topicId): void;
    
    public function deleteByQuestionIdAndTopicId(QuestionId $questionId, TopicId $topicId): void;
    
    public function countByQuestionId(QuestionId $questionId): int;
    
    public function countByTopicId(TopicId $topicId): int;
} 