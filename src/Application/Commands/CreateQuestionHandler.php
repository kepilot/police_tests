<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\ValueObjects\QuestionId;

final class CreateQuestionHandler
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository
    ) {
    }

    public function handle(CreateQuestionCommand $command): CreateQuestionResult
    {
        // Create a new question ID
        $questionId = QuestionId::generate();
        
        // Create the question entity
        $question = new \App\Domain\Entities\Question(
            $questionId,
            $command->text,
            $command->type,
            $command->options,
            $command->correctOption,
            $command->points,
            $command->examId
        );
        
        // Save to repository
        $this->questionRepository->save($question);
        
        return new CreateQuestionResult($questionId);
    }
}

final class CreateQuestionResult
{
    public function __construct(
        public readonly QuestionId $questionId
    ) {
    }
} 