<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;
use App\Domain\Entities\Question;

final class QuestionController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function listQuestions(): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $questions = $repository->findActive();

            $questionsData = [];
            foreach ($questions as $question) {
                $questionsData[] = [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'type_display' => $question->getType()->getDisplayName(),
                    'options' => $question->getOptions(),
                    'correct_option' => $question->getCorrectOption(),
                    'points' => $question->getPoints(),
                    'exam_id' => $question->getExamId()->toString(),
                    'topic_ids' => $question->getTopicIds(),
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
                'message' => 'Error listing questions: ' . $e->getMessage()
            ];
        }
    }

    public function createQuestion(string $text, string $type, string $examId, array $options, int $correctOption, int $points = 1, array $topicIds = []): array
    {
        try {
            $question = new Question(
                new QuestionText($text),
                new QuestionType($type),
                ExamId::fromString($examId),
                $options,
                $correctOption,
                $points
            );

            $repository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $repository->save($question);

            // Associate with topics if provided
            if (!empty($topicIds)) {
                $questionTopicService = $this->container->get(\App\Application\Services\QuestionTopicService::class);
                $topicIds = array_map(fn($id) => TopicId::fromString($id), $topicIds);
                $questionTopicService->setQuestionTopics($question->getId(), $topicIds);
            }

            return [
                'success' => true,
                'message' => 'Question created successfully',
                'data' => [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'options' => $question->getOptions(),
                    'correct_option' => $question->getCorrectOption(),
                    'points' => $question->getPoints(),
                    'exam_id' => $question->getExamId()->toString()
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
                'message' => 'Error creating question: ' . $e->getMessage()
            ];
        }
    }

    public function getQuestion(string $questionId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $question = $repository->findById(QuestionId::fromString($questionId));

            if (!$question) {
                return [
                    'success' => false,
                    'message' => 'Question not found'
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'type_display' => $question->getType()->getDisplayName(),
                    'options' => $question->getOptions(),
                    'correct_option' => $question->getCorrectOption(),
                    'points' => $question->getPoints(),
                    'exam_id' => $question->getExamId()->toString(),
                    'topic_ids' => $question->getTopicIds(),
                    'is_active' => $question->isActive(),
                    'created_at' => $question->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updated_at' => $question->getUpdatedAt()?->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching question: ' . $e->getMessage()
            ];
        }
    }

    public function updateQuestion(string $questionId, string $text, array $options, int $correctOption, int $points = 1, array $topicIds = []): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $question = $repository->findById(QuestionId::fromString($questionId));

            if (!$question) {
                return [
                    'success' => false,
                    'message' => 'Question not found'
                ];
            }

            $question->update(
                new QuestionText($text),
                $options,
                $correctOption,
                $points
            );

            $repository->save($question);

            // Update topic associations if provided
            if (!empty($topicIds)) {
                $questionTopicService = $this->container->get(\App\Application\Services\QuestionTopicService::class);
                $topicIds = array_map(fn($id) => TopicId::fromString($id), $topicIds);
                $questionTopicService->setQuestionTopics($question->getId(), $topicIds);
            }

            return [
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'options' => $question->getOptions(),
                    'correct_option' => $question->getCorrectOption(),
                    'points' => $question->getPoints(),
                    'exam_id' => $question->getExamId()->toString(),
                    'updated_at' => $question->getUpdatedAt()?->format('Y-m-d H:i:s')
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
                'message' => 'Error updating question: ' . $e->getMessage()
            ];
        }
    }

    public function deleteQuestion(string $questionId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $question = $repository->findById(QuestionId::fromString($questionId));

            if (!$question) {
                return [
                    'success' => false,
                    'message' => 'Question not found'
                ];
            }

            $question->delete();
            $repository->save($question);

            return [
                'success' => true,
                'message' => 'Question deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting question: ' . $e->getMessage()
            ];
        }
    }

    public function associateQuestionTopics(string $questionId, array $topicIds): array
    {
        try {
            $questionTopicService = $this->container->get(\App\Application\Services\QuestionTopicService::class);
            $topicIds = array_map(fn($id) => TopicId::fromString($id), $topicIds);
            $questionTopicService->setQuestionTopics(QuestionId::fromString($questionId), $topicIds);

            return [
                'success' => true,
                'message' => 'Question topics associated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error associating question topics: ' . $e->getMessage()
            ];
        }
    }

    public function disassociateQuestionTopic(string $questionId, string $topicId): array
    {
        try {
            $questionTopicService = $this->container->get(\App\Application\Services\QuestionTopicService::class);
            $questionTopicService->disassociateQuestionFromTopic(
                QuestionId::fromString($questionId),
                TopicId::fromString($topicId)
            );

            return [
                'success' => true,
                'message' => 'Question topic disassociated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error disassociating question topic: ' . $e->getMessage()
            ];
        }
    }

    public function getQuestionTopics(string $questionId): array
    {
        try {
            $questionTopicService = $this->container->get(\App\Application\Services\QuestionTopicService::class);
            $topics = $questionTopicService->getTopicsForQuestion(QuestionId::fromString($questionId));

            $topicsData = [];
            foreach ($topics as $topic) {
                $topicsData[] = [
                    'id' => $topic->getId()->toString(),
                    'title' => $topic->getTitle()->value(),
                    'description' => $topic->getDescription()->value(),
                    'level' => $topic->getLevel()->value(),
                    'level_display' => $topic->getLevel()->getDisplayName()
                ];
            }

            return [
                'success' => true,
                'data' => $topicsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching question topics: ' . $e->getMessage()
            ];
        }
    }
} 