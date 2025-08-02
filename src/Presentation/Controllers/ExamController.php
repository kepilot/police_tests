<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateExamCommand;
use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;

final class ExamController
{
    public function __construct(
        private readonly Container $container
    ) {
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

    public function updateExam(string $examId, string $title, string $description, int $durationMinutes, int $passingScorePercentage): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $exam = $repository->findById(ExamId::fromString($examId));

            if (!$exam) {
                return [
                    'success' => false,
                    'message' => 'Exam not found'
                ];
            }

            $exam->update(
                new ExamTitle($title),
                new ExamDescription($description),
                new ExamDuration($durationMinutes),
                new ExamPassingScore($passingScorePercentage)
            );

            $repository->save($exam);

            return [
                'success' => true,
                'message' => 'Exam updated successfully',
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
                'message' => 'Error updating exam: ' . $e->getMessage()
            ];
        }
    }

    public function getExam(string $examId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $exam = $repository->findById(ExamId::fromString($examId));

            if (!$exam) {
                return [
                    'success' => false,
                    'message' => 'Exam not found'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $exam->getId()->toString(),
                    'title' => $exam->getTitle()->value(),
                    'description' => $exam->getDescription()->value(),
                    'duration_minutes' => $exam->getDuration()->value(),
                    'duration_display' => $exam->getDuration()->getDisplayValue(),
                    'passing_score_percentage' => $exam->getPassingScore()->value(),
                    'passing_score_display' => $exam->getPassingScore()->getDisplayValue(),
                    'topic_id' => $exam->getTopicId()->toString(),
                    'is_active' => $exam->isActive(),
                    'created_at' => $exam->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $exam->getUpdatedAt()?->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching exam: ' . $e->getMessage()
            ];
        }
    }

    public function deleteExam(string $examId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $exam = $repository->findById(ExamId::fromString($examId));

            if (!$exam) {
                return [
                    'success' => false,
                    'message' => 'Exam not found'
                ];
            }

            $exam->delete();
            $repository->save($exam);

            return [
                'success' => true,
                'message' => 'Exam deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting exam: ' . $e->getMessage()
            ];
        }
    }

    public function getLearningStats(): array
    {
        try {
            $topicRepository = $this->container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
            $examRepository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $questionRepository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $attemptRepository = $this->container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);

            $totalTopics = $topicRepository->count();
            $totalExams = $examRepository->count();
            $totalQuestions = $questionRepository->count();
            $totalAttempts = $attemptRepository->count();

            $stats = [
                'topics' => [
                    'total' => $totalTopics,
                    'active' => count($topicRepository->findActive())
                ],
                'exams' => [
                    'total' => $totalExams,
                    'active' => count($examRepository->findActive())
                ],
                'questions' => [
                    'total' => $totalQuestions,
                    'active' => count($questionRepository->findActive())
                ],
                'attempts' => [
                    'total' => $totalAttempts,
                    'completed' => count($attemptRepository->findCompleted()),
                    'passed' => count($attemptRepository->findPassed())
                ]
            ];

            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching learning stats: ' . $e->getMessage()
            ];
        }
    }
} 