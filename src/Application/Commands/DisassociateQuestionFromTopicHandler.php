<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Application\Services\QuestionTopicService;

final class DisassociateQuestionFromTopicHandler
{
    public function __construct(
        private readonly QuestionTopicService $questionTopicService
    ) {
    }

    public function handle(DisassociateQuestionFromTopicCommand $command): void
    {
        $this->questionTopicService->disassociateQuestionFromTopic(
            $command->questionId,
            $command->topicId
        );
    }
} 