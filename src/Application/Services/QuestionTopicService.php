<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Entities\Question;
use App\Domain\Entities\Topic;
use App\Domain\Entities\QuestionTopic;
use App\Domain\Repositories\QuestionRepositoryInterface;
use App\Domain\Repositories\TopicRepositoryInterface;
use App\Domain\Repositories\QuestionTopicRepositoryInterface;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;

final class QuestionTopicService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly TopicRepositoryInterface $topicRepository,
        private readonly QuestionTopicRepositoryInterface $questionTopicRepository
    ) {
    }

    public function associateQuestionWithTopic(QuestionId $questionId, TopicId $topicId): void
    {
        // Verify that both question and topic exist
        $question = $this->questionRepository->findById($questionId);
        if (!$question) {
            throw new \InvalidArgumentException('Question not found');
        }

        $topic = $this->topicRepository->findById($topicId);
        if (!$topic) {
            throw new \InvalidArgumentException('Topic not found');
        }

        // Check if association already exists
        $existingAssociation = $this->questionTopicRepository->findByQuestionIdAndTopicId($questionId, $topicId);
        if ($existingAssociation) {
            return; // Association already exists
        }

        // Create new association
        $questionTopic = new QuestionTopic($questionId, $topicId);
        $this->questionTopicRepository->save($questionTopic);

        // Update question entity with new topic ID
        $question->addTopicId($topicId);
        $this->questionRepository->save($question);
    }

    public function disassociateQuestionFromTopic(QuestionId $questionId, TopicId $topicId): void
    {
        // Verify that both question and topic exist
        $question = $this->questionRepository->findById($questionId);
        if (!$question) {
            throw new \InvalidArgumentException('Question not found');
        }

        $topic = $this->topicRepository->findById($topicId);
        if (!$topic) {
            throw new \InvalidArgumentException('Topic not found');
        }

        // Remove association
        $this->questionTopicRepository->deleteByQuestionIdAndTopicId($questionId, $topicId);

        // Update question entity
        $question->removeTopicId($topicId);
        $this->questionRepository->save($question);
    }

    public function getTopicsForQuestion(QuestionId $questionId): array
    {
        return $this->topicRepository->findByQuestionId($questionId);
    }

    public function getActiveTopicsForQuestion(QuestionId $questionId): array
    {
        return $this->topicRepository->findActiveByQuestionId($questionId);
    }

    public function getQuestionsForTopic(TopicId $topicId): array
    {
        return $this->questionRepository->findByTopicId($topicId);
    }

    public function getActiveQuestionsForTopic(TopicId $topicId): array
    {
        return $this->questionRepository->findActiveByTopicId($topicId);
    }

    public function setQuestionTopics(QuestionId $questionId, array $topicIds): void
    {
        // Verify that question exists
        $question = $this->questionRepository->findById($questionId);
        if (!$question) {
            throw new \InvalidArgumentException('Question not found');
        }

        // Verify that all topics exist
        foreach ($topicIds as $topicId) {
            $topic = $this->topicRepository->findById($topicId);
            if (!$topic) {
                throw new \InvalidArgumentException("Topic with ID {$topicId->toString()} not found");
            }
        }

        // Remove all existing associations for this question
        $this->questionTopicRepository->deleteByQuestionId($questionId);

        // Create new associations
        foreach ($topicIds as $topicId) {
            $questionTopic = new QuestionTopic($questionId, $topicId);
            $this->questionTopicRepository->save($questionTopic);
        }

        // Update question entity
        $question->setTopicIds($topicIds);
        $this->questionRepository->save($question);
    }

    public function clearQuestionTopics(QuestionId $questionId): void
    {
        // Verify that question exists
        $question = $this->questionRepository->findById($questionId);
        if (!$question) {
            throw new \InvalidArgumentException('Question not found');
        }

        // Remove all associations
        $this->questionTopicRepository->deleteByQuestionId($questionId);

        // Update question entity
        $question->clearTopicIds();
        $this->questionRepository->save($question);
    }

    public function getQuestionTopicCount(QuestionId $questionId): int
    {
        return $this->questionTopicRepository->countByQuestionId($questionId);
    }

    public function getTopicQuestionCount(TopicId $topicId): int
    {
        return $this->questionTopicRepository->countByTopicId($topicId);
    }
} 