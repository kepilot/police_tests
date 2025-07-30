<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Application\Services\QuestionTopicService;

final class AssociateQuestionWithTopicHandler
{
    public function __construct(
        private readonly QuestionTopicService $questionTopicService
    ) {
    }

    public function handle(AssociateQuestionWithTopicCommand $command): void
    {
        $this->questionTopicService->associateQuestionWithTopic(
            $command->questionId,
            $command->topicId
        );
    }
} 