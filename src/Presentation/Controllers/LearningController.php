<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;

/**
 * LearningController - Facade for learning management operations
 * 
 * This controller acts as a coordinator for the various learning-related controllers,
 * providing a unified interface while delegating specific responsibilities to
 * focused controllers following DDD principles.
 */
final class LearningController
{
    public function __construct(
        private readonly Container $container
    ) {
    }

    // Topic Management - Delegates to TopicController
    public function listTopics(): array
    {
        return $this->container->get(TopicController::class)->listTopics();
    }

    public function createTopic(string $title, string $description, string $level): array
    {
        return $this->container->get(TopicController::class)->createTopic($title, $description, $level);
    }

    public function updateTopic(string $topicId, string $title, string $description, string $level): array
    {
        return $this->container->get(TopicController::class)->updateTopic($topicId, $title, $description, $level);
    }

    public function deleteTopic(string $topicId): array
    {
        return $this->container->get(TopicController::class)->deleteTopic($topicId);
    }

    public function getTopic(string $topicId): array
    {
        return $this->container->get(TopicController::class)->getTopic($topicId);
    }

    public function getTopicQuestions(string $topicId): array
    {
        return $this->container->get(TopicController::class)->getTopicQuestions($topicId);
    }

    // Exam Management - Delegates to ExamController
    public function listExams(): array
    {
        return $this->container->get(ExamController::class)->listExams();
    }

    public function createExam(string $title, string $description, int $durationMinutes, int $passingScorePercentage, string $topicId): array
    {
        return $this->container->get(ExamController::class)->createExam($title, $description, $durationMinutes, $passingScorePercentage, $topicId);
    }

    public function updateExam(string $examId, string $title, string $description, int $durationMinutes, int $passingScorePercentage): array
    {
        return $this->container->get(ExamController::class)->updateExam($examId, $title, $description, $durationMinutes, $passingScorePercentage);
    }

    public function getExam(string $examId): array
    {
        return $this->container->get(ExamController::class)->getExam($examId);
    }

    public function deleteExam(string $examId): array
    {
        return $this->container->get(ExamController::class)->deleteExam($examId);
    }

    public function getLearningStats(): array
    {
        return $this->container->get(ExamController::class)->getLearningStats();
    }

    // Question Management - Delegates to QuestionController
    public function listQuestions(): array
    {
        return $this->container->get(QuestionController::class)->listQuestions();
    }

    public function createQuestion(string $text, string $type, string $examId, array $options, int $correctOption, int $points = 1, array $topicIds = []): array
    {
        return $this->container->get(QuestionController::class)->createQuestion($text, $type, $examId, $options, $correctOption, $points, $topicIds);
    }

    public function getQuestion(string $questionId): array
    {
        return $this->container->get(QuestionController::class)->getQuestion($questionId);
    }

    public function updateQuestion(string $questionId, string $text, array $options, int $correctOption, int $points = 1, array $topicIds = []): array
    {
        return $this->container->get(QuestionController::class)->updateQuestion($questionId, $text, $options, $correctOption, $points, $topicIds);
    }

    public function deleteQuestion(string $questionId): array
    {
        return $this->container->get(QuestionController::class)->deleteQuestion($questionId);
    }

    public function associateQuestionTopics(string $questionId, array $topicIds): array
    {
        return $this->container->get(QuestionController::class)->associateQuestionTopics($questionId, $topicIds);
    }

    public function disassociateQuestionTopic(string $questionId, string $topicId): array
    {
        return $this->container->get(QuestionController::class)->disassociateQuestionTopic($questionId, $topicId);
    }

    public function getQuestionTopics(string $questionId): array
    {
        return $this->container->get(QuestionController::class)->getQuestionTopics($questionId);
    }

    // Exam Assignment Management - Delegates to ExamAssignmentController
    public function assignExamToUser(string $userId, string $examId, string $assignedBy, ?string $dueDate = null): array
    {
        return $this->container->get(ExamAssignmentController::class)->assignExamToUser($userId, $examId, $assignedBy, $dueDate);
    }

    public function getUserAssignments(string $userId): array
    {
        return $this->container->get(ExamAssignmentController::class)->getUserAssignments($userId);
    }

    public function getPendingAssignments(string $userId): array
    {
        return $this->container->get(ExamAssignmentController::class)->getPendingAssignments($userId);
    }

    public function getOverdueAssignments(string $userId): array
    {
        return $this->container->get(ExamAssignmentController::class)->getOverdueAssignments($userId);
    }

    public function markAssignmentAsCompleted(string $assignmentId): array
    {
        return $this->container->get(ExamAssignmentController::class)->markAssignmentAsCompleted($assignmentId);
    }

    public function getAssignmentStatistics(string $userId): array
    {
        return $this->container->get(ExamAssignmentController::class)->getAssignmentStatistics($userId);
    }

    // Exam Attempt Management - Delegates to ExamAttemptController
    public function startExamAttempt(string $userId, string $examId): array
    {
        return $this->container->get(ExamAttemptController::class)->startExamAttempt($userId, $examId);
    }

    public function submitExamAttempt(string $attemptId, array $answers): array
    {
        return $this->container->get(ExamAttemptController::class)->submitExamAttempt($attemptId, $answers);
    }

    public function getUserAttempts(string $userId): array
    {
        return $this->container->get(ExamAttemptController::class)->getUserAttempts($userId);
    }

    public function getAttemptStatistics(string $userId): array
    {
        return $this->container->get(ExamAttemptController::class)->getAttemptStatistics($userId);
    }
} 