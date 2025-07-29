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
        $topic = new Topic(
            $command->getTitle(),
            $command->getDescription(),
            $command->getLevel()
        );

        $this->topicRepository->save($topic);

        return $topic;
    }
} 