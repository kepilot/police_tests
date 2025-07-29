<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateTopicCommand;
use App\Application\Commands\CreateExamCommand;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use App\Domain\ValueObjects\TopicId;

final class LearningController
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
            $topic = $handler($command);

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

    public function listExams(): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $exams = $repository->findActive();

            $examsData = [];
            foreach ($exams as $exam) {
                $examsData[] = [
                    'id' => $exam->getId()->toString(),
                    'title' => $exam->getTitle()->value(),
                    'description' => $exam->getDescription()->value(),
                    'duration_minutes' => $exam->getDuration()->value(),
                    'duration_display' => $exam->getDuration()->getDisplayValue(),
                    'passing_score_percentage' => $exam->getPassingScore()->value(),
                    'passing_score_display' => $exam->getPassingScore()->getDisplayValue(),
                    'topic_id' => $exam->getTopicId()->toString(),
                    'is_active' => $exam->isActive(),
                    'created_at' => $exam->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            return [
                'success' => true,
                'data' => $examsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error listing exams: ' . $e->getMessage()
            ];
        }
    }

    public function createExam(string $title, string $description, int $durationMinutes, int $passingScorePercentage, string $topicId): array
    {
        try {
            $command = new CreateExamCommand(
                new ExamTitle($title),
                new ExamDescription($description),
                new ExamDuration($durationMinutes),
                new ExamPassingScore($passingScorePercentage),
                TopicId::fromString($topicId)
            );

            $handler = $this->container->get(\App\Application\Commands\CreateExamHandler::class);
            $exam = $handler($command);

            return [
                'success' => true,
                'message' => 'Exam created successfully',
                'data' => [
                    'id' => $exam->getId()->toString(),
                    'title' => $exam->getTitle()->value(),
                    'description' => $exam->getDescription()->value(),
                    'duration_minutes' => $exam->getDuration()->value(),
                    'duration_display' => $exam->getDuration()->getDisplayValue(),
                    'passing_score_percentage' => $exam->getPassingScore()->value(),
                    'passing_score_display' => $exam->getPassingScore()->getDisplayValue(),
                    'topic_id' => $exam->getTopicId()->toString()
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
                'message' => 'Error creating exam: ' . $e->getMessage()
            ];
        }
    }

    public function getLearningStats(): array
    {
        try {
            $topicRepository = $this->container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
            $examRepository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $userRepository = $this->container->get(\App\Domain\Repositories\UserRepositoryInterface::class);

            $totalTopics = $topicRepository->count();
            $totalExams = $examRepository->count();
            $totalUsers = $userRepository->count();

            // Count topics by level
            $beginnerTopics = $topicRepository->countByLevel(TopicLevel::beginner());
            $intermediateTopics = $topicRepository->countByLevel(TopicLevel::intermediate());
            $advancedTopics = $topicRepository->countByLevel(TopicLevel::advanced());
            $expertTopics = $topicRepository->countByLevel(TopicLevel::expert());

            return [
                'success' => true,
                'data' => [
                    'total_topics' => $totalTopics,
                    'total_exams' => $totalExams,
                    'total_users' => $totalUsers,
                    'topics_by_level' => [
                        'beginner' => $beginnerTopics,
                        'intermediate' => $intermediateTopics,
                        'advanced' => $advancedTopics,
                        'expert' => $expertTopics
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting learning stats: ' . $e->getMessage()
            ];
        }
    }
} 