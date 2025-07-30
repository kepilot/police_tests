<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;

final class AssociateQuestionWithTopicCommand
{
    public function __construct(
        public readonly QuestionId $questionId,
        public readonly TopicId $topicId
    ) {
    }
} 