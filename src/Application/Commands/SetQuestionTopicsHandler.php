<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Application\Services\QuestionTopicService;

final class SetQuestionTopicsHandler
{
    public function __construct(
        private readonly QuestionTopicService $questionTopicService
    ) {
    }

    public function handle(SetQuestionTopicsCommand $command): void
    {
        $this->questionTopicService->setQuestionTopics(
            $command->questionId,
            $command->topicIds
        );
    }
} 