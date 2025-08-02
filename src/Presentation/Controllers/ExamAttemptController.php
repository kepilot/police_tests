<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\ExamAttemptId;
use App\Domain\ValueObjects\ExamScore;
use App\Domain\Entities\ExamAttempt;

final class ExamAttemptController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    public function startExamAttempt(string $userId, string $examId): array
    {
        try {
            $userRepository = $this->container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
            $examRepository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
            $questionRepository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $attemptRepository = $this->container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);

            // Validate user and exam exist
            $user = $userRepository->findById(\Ramsey\Uuid\Uuid::fromString($userId));
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            $exam = $examRepository->findById(ExamId::fromString($examId));
            if (!$exam) {
                return [
                    'success' => false,
                    'message' => 'Exam not found'
                ];
            }

            if (!$exam->isActive()) {
                return [
                    'success' => false,
                    'message' => 'Exam is not active'
                ];
            }

            // Check if there's already an active attempt
            $existingAttempt = $attemptRepository->findByUserIdAndExamId(
                UserId::fromString($userId),
                ExamId::fromString($examId)
            );

            if ($existingAttempt && !$existingAttempt->isCompleted()) {
                return [
                    'success' => false,
                    'message' => 'You already have an active attempt for this exam'
                ];
            }

            // Create new attempt
            $attempt = new ExamAttempt(
                UserId::fromString($userId),
                ExamId::fromString($examId)
            );

            $attemptRepository->save($attempt);

            // Get exam questions (without correct answers)
            $questions = $questionRepository->findByExamId(ExamId::fromString($examId));
            $questionsData = [];

            foreach ($questions as $question) {
                $questionsData[] = [
                    'id' => $question->getId()->toString(),
                    'text' => $question->getText()->value(),
                    'type' => $question->getType()->value(),
                    'options' => $question->getOptions(),
                    'points' => $question->getPoints()
                ];
            }

            return [
                'success' => true,
                'message' => 'Exam attempt started successfully',
                'data' => [
                    'attempt_id' => $attempt->getId()->toString(),
                    'exam' => [
                        'id' => $exam->getId()->toString(),
                        'title' => $exam->getTitle()->value(),
                        'description' => $exam->getDescription()->value(),
                        'duration_minutes' => $exam->getDuration()->value(),
                        'passing_score_percentage' => $exam->getPassingScore()->value()
                    ],
                    'questions' => $questionsData,
                    'started_at' => $attempt->getStartedAt()->format('Y-m-d H:i:s'),
                    'time_limit' => $exam->getDuration()->value() * 60 // Convert to seconds
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error starting exam attempt: ' . $e->getMessage()
            ];
        }
    }

    public function submitExamAttempt(string $attemptId, array $answers): array
    {
        try {
            $attemptRepository = $this->container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);
            $questionRepository = $this->container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
            $examRepository = $this->container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);

            $attempt = $attemptRepository->findById(ExamAttemptId::fromString($attemptId));
            if (!$attempt) {
                return [
                    'success' => false,
                    'message' => 'Exam attempt not found'
                ];
            }

            if ($attempt->isCompleted()) {
                return [
                    'success' => false,
                    'message' => 'Exam attempt already completed'
                ];
            }

            // Get exam and questions
            $exam = $examRepository->findById($attempt->getExamId());
            $questions = $questionRepository->findByExamId($attempt->getExamId());

            // Calculate score
            $totalPoints = 0;
            $earnedPoints = 0;
            $questionResults = [];

            foreach ($questions as $question) {
                $questionId = $question->getId()->toString();
                $totalPoints += $question->getPoints();

                if (isset($answers[$questionId])) {
                    $selectedAnswer = $answers[$questionId];
                    $isCorrect = $question->isCorrect($selectedAnswer);
                    $earnedPoints += $isCorrect ? $question->getPoints() : 0;

                    $questionResults[] = [
                        'question_id' => $questionId,
                        'text' => $question->getText()->value(),
                        'selected_answer' => $selectedAnswer,
                        'correct_answer' => $question->getCorrectOption(),
                        'is_correct' => $isCorrect,
                        'points' => $question->getPoints(),
                        'earned_points' => $isCorrect ? $question->getPoints() : 0
                    ];
                } else {
                    $questionResults[] = [
                        'question_id' => $questionId,
                        'text' => $question->getText()->value(),
                        'selected_answer' => null,
                        'correct_answer' => $question->getCorrectOption(),
                        'is_correct' => false,
                        'points' => $question->getPoints(),
                        'earned_points' => 0
                    ];
                }
            }

            // Calculate percentage score
            $percentageScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
            $passed = $percentageScore >= $exam->getPassingScore()->value();

            // Complete the attempt
            $attempt->complete(
                new ExamScore($earnedPoints),
                $passed
            );

            $attemptRepository->save($attempt);

            return [
                'success' => true,
                'message' => 'Exam submitted successfully',
                'data' => [
                    'attempt_id' => $attempt->getId()->toString(),
                    'exam_title' => $exam->getTitle()->value(),
                    'score' => [
                        'earned' => $earnedPoints,
                        'total' => $totalPoints,
                        'percentage' => $percentageScore,
                        'passed' => $passed,
                        'passing_threshold' => $exam->getPassingScore()->value()
                    ],
                    'completion_time' => $attempt->getCompletedAt()->format('Y-m-d H:i:s'),
                    'duration_minutes' => $attempt->getDurationMinutes(),
                    'question_results' => $questionResults
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error submitting exam attempt: ' . $e->getMessage()
            ];
        }
    }

    public function getUserAttempts(string $userId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);
            $attempts = $repository->findByUserId(UserId::fromString($userId));

            $attemptsData = [];
            foreach ($attempts as $attempt) {
                $attemptsData[] = [
                    'id' => $attempt->getId()->toString(),
                    'exam_id' => $attempt->getExamId()->toString(),
                    'started_at' => $attempt->getStartedAt()->format('Y-m-d H:i:s'),
                    'completed_at' => $attempt->getCompletedAt()?->format('Y-m-d H:i:s'),
                    'is_completed' => $attempt->isCompleted(),
                    'score' => $attempt->getScore()?->value(),
                    'total_points' => $attempt->getTotalPoints(),
                    'percentage_score' => $attempt->getPercentageScore(),
                    'passed' => $attempt->isPassed(),
                    'duration_minutes' => $attempt->getDurationMinutes()
                ];
            }

            return [
                'success' => true,
                'data' => $attemptsData
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching user attempts: ' . $e->getMessage()
            ];
        }
    }

    public function getAttemptStatistics(string $userId): array
    {
        try {
            $repository = $this->container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);
            
            $totalAttempts = $repository->countByUserId(UserId::fromString($userId));
            $completedAttempts = count($repository->findCompletedByUserId(UserId::fromString($userId)));
            $passedAttempts = count($repository->findPassedByUserId(UserId::fromString($userId)));

            $averageScore = $repository->getAverageScoreByUserId(UserId::fromString($userId));
            $passRate = $completedAttempts > 0 ? round(($passedAttempts / $completedAttempts) * 100, 2) : 0;

            return [
                'success' => true,
                'data' => [
                    'total_attempts' => $totalAttempts,
                    'completed_attempts' => $completedAttempts,
                    'passed_attempts' => $passedAttempts,
                    'average_score' => $averageScore,
                    'pass_rate' => $passRate
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching attempt statistics: ' . $e->getMessage()
            ];
        }
    }
} 