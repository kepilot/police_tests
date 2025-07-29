<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Entities\Exam;
use App\Domain\Repositories\ExamRepositoryInterface;
use App\Domain\Repositories\TopicRepositoryInterface;

final class CreateExamHandler
{
    public function __construct(
        private readonly ExamRepositoryInterface $examRepository,
        private readonly TopicRepositoryInterface $topicRepository
    ) {
    }

    public function __invoke(CreateExamCommand $command): Exam
    {
        // Verify that the topic exists
        $topic = $this->topicRepository->findById($command->getTopicId());
        if (!$topic) {
            throw new \InvalidArgumentException('Topic not found');
        }

        $exam = new Exam(
            $command->getTitle(),
            $command->getDescription(),
            $command->getDuration(),
            $command->getPassingScore(),
            $command->getTopicId()
        );

        $this->examRepository->save($exam);

        return $exam;
    }
} 