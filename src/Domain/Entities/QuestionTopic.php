<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\QuestionTopicId;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;
use DateTimeImmutable;

final class QuestionTopic
{
    private QuestionTopicId $id;
    private QuestionId $questionId;
    private TopicId $topicId;
    private DateTimeImmutable $createdAt;

    public function __construct(
        QuestionId $questionId,
        TopicId $topicId
    ) {
        $this->id = QuestionTopicId::generate();
        $this->questionId = $questionId;
        $this->topicId = $topicId;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): QuestionTopicId
    {
        return $this->id;
    }

    public function getQuestionId(): QuestionId
    {
        return $this->questionId;
    }

    public function getTopicId(): TopicId
    {
        return $this->topicId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
} 