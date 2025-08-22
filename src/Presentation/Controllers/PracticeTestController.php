<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\Repositories\TopicRepositoryInterface;
use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\QuestionId;

final class PracticeTestController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    /**
     * Get available topics for practice tests
     */
    public function getAvailableTopics(): array
    {
        try {
            $repository = $this->container->get(TopicRepositoryInterface::class);
            $topics = $repository->findActive();

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
                'message' => 'Error fetching topics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a practice test with random questions
     */
    public function createPracticeTest(array $requestData): array
    {
        try {
            $topicIds = $requestData['topic_ids'] ?? [];
            $questionCount = min((int)($requestData['question_count'] ?? 10), 50); // Max 50 questions
            $difficulty = $requestData['difficulty'] ?? 'all'; // all, beginner, intermediate, advanced, expert

            if (empty($topicIds)) {
                return [
                    'success' => false,
                    'message' => 'At least one topic must be selected'
                ];
            }

            $questionRepository = $this->container->get(QuestionRepositoryInterface::class);
            $topicRepository = $this->container->get(TopicRepositoryInterface::class);

            // Get questions for selected topics
            $allQuestions = [];
            foreach ($topicIds as $topicId) {
                $topic = $topicRepository->findById(TopicId::fromString($topicId));
                if (!$topic || !$topic->isActive()) {
                    continue;
                }

                $questions = $questionRepository->findActiveByTopicId(TopicId::fromString($topicId));
                
                // Filter by difficulty if specified
                if ($difficulty !== 'all') {
                    $questions = array_filter($questions, function($q) use ($topic, $difficulty) {
                        return $topic->getLevel()->value() === $difficulty;
                    });
                }

                $allQuestions = array_merge($allQuestions, $questions);
            }

            if (empty($allQuestions)) {
                return [
                    'success' => false,
                    'message' => 'No questions found for the selected topics and difficulty level'
                ];
            }

            // Shuffle questions and select the requested number
            shuffle($allQuestions);
            $selectedQuestions = array_slice($allQuestions, 0, $questionCount);

            // Prepare questions data (without correct answers for practice)
            $questionsData = [];
            foreach ($selectedQuestions as $question) {
                $questionsData[] = [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'options' => $question->getOptions(),
                    'points' => $question->getPoints()
                ];
            }

            // Generate practice test ID
            $practiceTestId = 'practice_' . uniqid();

            return [
                'success' => true,
                'data' => [
                    'practice_test_id' => $practiceTestId,
                    'questions' => $questionsData,
                    'total_questions' => count($questionsData),
                    'selected_topics' => $topicIds,
                    'difficulty' => $difficulty,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating practice test: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Submit practice test answers and get results
     */
    public function submitPracticeTest(array $requestData): array
    {
        try {
            $practiceTestId = $requestData['practice_test_id'] ?? '';
            $answers = $requestData['answers'] ?? [];

            if (empty($practiceTestId) || empty($answers)) {
                return [
                    'success' => false,
                    'message' => 'Practice test ID and answers are required'
                ];
            }

            $questionRepository = $this->container->get(QuestionRepositoryInterface::class);
            
            $totalPoints = 0;
            $earnedPoints = 0;
            $questionResults = [];
            $correctAnswers = 0;

            foreach ($answers as $questionId => $selectedAnswer) {
                try {
                    $question = $questionRepository->findById(QuestionId::fromString($questionId));
                    if (!$question) {
                        continue;
                    }

                    $totalPoints += $question->getPoints();
                    $isCorrect = $question->isCorrect($selectedAnswer);
                    
                    if ($isCorrect) {
                        $earnedPoints += $question->getPoints();
                        $correctAnswers++;
                    }

                    $questionResults[] = [
                        'question_id' => $questionId,
                        'text' => $question->getText()->value(),
                        'selected_answer' => $selectedAnswer,
                        'correct_answer' => $question->getCorrectOption(),
                        'is_correct' => $isCorrect,
                        'points' => $question->getPoints(),
                        'earned_points' => $isCorrect ? $question->getPoints() : 0
                    ];

                } catch (\Exception $e) {
                    // Skip invalid questions
                    continue;
                }
            }

            $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
            $passed = $percentage >= 70; // 70% passing threshold for practice tests

            return [
                'success' => true,
                'data' => [
                    'practice_test_id' => $practiceTestId,
                    'total_questions' => count($questionResults),
                    'correct_answers' => $correctAnswers,
                    'total_points' => $totalPoints,
                    'earned_points' => $earnedPoints,
                    'percentage' => $percentage,
                    'passed' => $passed,
                    'question_results' => $questionResults,
                    'submitted_at' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error submitting practice test: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get practice test statistics for a user
     */
    public function getPracticeTestStats(string $userId): array
    {
        try {
            // This would typically query a practice_attempts table
            // For now, return mock data structure
            return [
                'success' => true,
                'data' => [
                    'total_practice_tests' => 0,
                    'total_questions_answered' => 0,
                    'average_score' => 0,
                    'best_score' => 0,
                    'topics_practiced' => [],
                    'recent_attempts' => []
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching practice test statistics: ' . $e->getMessage()
            ];
        }
    }
}
