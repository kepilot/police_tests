<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;

final class SetQuestionTopicsCommand
{
    /**
     * @param TopicId[] $topicIds
     */
    public function __construct(
        public readonly QuestionId $questionId,
        public readonly array $topicIds
    ) {
    }
} 