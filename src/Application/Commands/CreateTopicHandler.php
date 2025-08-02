<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\Entities\Topic;
use App\Domain\Repositories\TopicRepositoryInterface;

final class CreateTopicHandler
{
    public function __construct(
        private readonly TopicRepositoryInterface $topicRepository
    ) {
    }

    public function __invoke(CreateTopicCommand $command): Topic
    {
        // Check if a topic with the same title and level already exists
        $existingTopic = $this->topicRepository->findByTitleAndLevel(
            $command->getTitle()->value(),
            $command->getLevel()->value()
        );

        if ($existingTopic) {
            throw new \InvalidArgumentException(
                "A topic with title '{$command->getTitle()->value()}' and level '{$command->getLevel()->value()}' already exists"
            );
        }

        $topic = new Topic(
            $command->getTitle(),
            $command->getDescription(),
            $command->getLevel()
        );

        $this->topicRepository->save($topic);

        return $topic;
    }
} 