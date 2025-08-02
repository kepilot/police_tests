<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateTopicCommand;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use App\Domain\ValueObjects\TopicId;

final class TopicController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function listTopics(): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
            $topics = $repository->findActive();

            $topicsData = [];
            foreach ($topics as $topic) {
                $topicsData[] = [
                    'id' => $topic->getId()->toString(),
                    'title' => $topic->getTitle()->value(),
                    'description' => $topic->getDescription()->value(),
                    'level' => $topic->getLevel()->value(),
                    'level_display' => $topic->getLevel()->getDisplayName(),
                    'is_active' => $topic->isActive(),
                    'created_at' => $topic->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'data' => $topicsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error listing topics: ' . $e->getMessage()
            ];
        }
    }

    public function createTopic(string $title, string $description, string $level): array
    {
        try {
            $command = new CreateTopicCommand(
                new TopicTitle($title),
                new TopicDescription($description),
                new TopicLevel($level)
            );

            $handler = $this->container->get(\App\Application\Commands\CreateTopicHandler::class);
            $topic = $handler->__invoke($command);

            return [
                'success' => true,
                'message' => 'Topic created successfully',
                'data' => [
                    'id' => $topic->getId()->toString(),
                    'title' => $topic->getTitle()->value(),
                    'description' => $topic->getDescription()->value(),
                    'level' => $topic->getLevel()->value(),
                    'level_display' => $topic->getLevel()->getDisplayName()
                ]
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => 'Invalid data: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating topic: ' . $e->getMessage()
            ];
        }
    }

    public function updateTopic(string $topicId, string $title, string $description, string $level): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
            $topic = $repository->findById(TopicId::fromString($topicId));

            if (!$topic) {
                return [
                    'success' => false,
                    'message' => 'Topic not found'
                ];
            }

            $topic->update(
                new TopicTitle($title),
                new TopicDescription($description),
                new TopicLevel($level)
            );

            $repository->save($topic);

            return [
                'success' => true,
                'message' => 'Topic updated successfully',
                'data' => [
                    'id' => $topic->getId()->toString(),
                    'title' => $topic->getTitle()->value(),
                    'description' => $topic->getDescription()->value(),
                    'level' => $topic->getLevel()->value(),
                    'level_display' => $topic->getLevel()->getDisplayName(),
                    'is_active' => $topic->isActive(),
                    'updated_at' => $topic->getUpdatedAt()?->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => 'Invalid data: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating topic: ' . $e->getMessage()
            ];
        }
    }

    public function deleteTopic(string $topicId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
            $topic = $repository->findById(TopicId::fromString($topicId));

            if (!$topic) {
                return [
                    'success' => false,
                    'message' => 'Topic not found'
                ];
            }

            $topic->delete();
            $repository->save($topic);

            return [
                'success' => true,
                'message' => 'Topic deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting topic: ' . $e->getMessage()
            ];
        }
    }

    public function getTopic(string $topicId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
            $topic = $repository->findById(TopicId::fromString($topicId));

            if (!$topic) {
                return [
                    'success' => false,
                    'message' => 'Topic not found'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $topic->getId()->toString(),
                    'title' => $topic->getTitle()->value(),
                    'description' => $topic->getDescription()->value(),
                    'level' => $topic->getLevel()->value(),
                    'level_display' => $topic->getLevel()->getDisplayName(),
                    'is_active' => $topic->isActive(),
                    'created_at' => $topic->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $topic->getUpdatedAt()?->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching topic: ' . $e->getMessage()
            ];
        }
    }

    public function getTopicQuestions(string $topicId): array
    {
        try {
            $questionTopicService = $this->container->get(\App\Application\Services\QuestionTopicService::class);
            $questions = $questionTopicService->getQuestionsForTopic(TopicId::fromString($topicId));

            $questionsData = [];
            foreach ($questions as $question) {
                $questionsData[] = [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'options' => $question->getOptions(),
                    'points' => $question->getPoints(),
                    'exam_id' => $question->getExamId()->toString(),
                    'is_active' => $question->isActive(),
                    'created_at' => $question->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'data' => $questionsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching topic questions: ' . $e->getMessage()
            ];
        }
    }
} 