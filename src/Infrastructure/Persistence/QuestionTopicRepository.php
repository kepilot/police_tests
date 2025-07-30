<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\QuestionTopic;
use App\Domain\Repositories\QuestionTopicRepositoryInterface;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\QuestionTopicId;
use PDO;

final class QuestionTopicRepository implements QuestionTopicRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(QuestionTopic $questionTopic): void
    {
        $sql = "INSERT INTO question_topics (id, question_id, topic_id, created_at) 
                VALUES (:id, :question_id, :topic_id, :created_at)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $questionTopic->getId()->toString(),
            'question_id' => $questionTopic->getQuestionId()->toString(),
            'topic_id' => $questionTopic->getTopicId()->toString(),
            'created_at' => $questionTopic->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    public function findByQuestionId(QuestionId $questionId): array
    {
        $sql = "SELECT id, question_id, topic_id, created_at 
                FROM question_topics 
                WHERE question_id = :question_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['question_id' => $questionId->toString()]);
        
        $questionTopics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questionTopics[] = new QuestionTopic(
                QuestionId::fromString($row['question_id']),
                TopicId::fromString($row['topic_id'])
            );
        }
        
        return $questionTopics;
    }

    public function findByTopicId(TopicId $topicId): array
    {
        $sql = "SELECT id, question_id, topic_id, created_at 
                FROM question_topics 
                WHERE topic_id = :topic_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        
        $questionTopics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $questionTopics[] = new QuestionTopic(
                QuestionId::fromString($row['question_id']),
                TopicId::fromString($row['topic_id'])
            );
        }
        
        return $questionTopics;
    }

    public function findByQuestionIdAndTopicId(QuestionId $questionId, TopicId $topicId): ?QuestionTopic
    {
        $sql = "SELECT id, question_id, topic_id, created_at 
                FROM question_topics 
                WHERE question_id = :question_id AND topic_id = :topic_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'question_id' => $questionId->toString(),
            'topic_id' => $topicId->toString()
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        
        return new QuestionTopic(
            QuestionId::fromString($row['question_id']),
            TopicId::fromString($row['topic_id'])
        );
    }

    public function deleteByQuestionId(QuestionId $questionId): void
    {
        $sql = "DELETE FROM question_topics WHERE question_id = :question_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['question_id' => $questionId->toString()]);
    }

    public function deleteByTopicId(TopicId $topicId): void
    {
        $sql = "DELETE FROM question_topics WHERE topic_id = :topic_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
    }

    public function deleteByQuestionIdAndTopicId(QuestionId $questionId, TopicId $topicId): void
    {
        $sql = "DELETE FROM question_topics 
                WHERE question_id = :question_id AND topic_id = :topic_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'question_id' => $questionId->toString(),
            'topic_id' => $topicId->toString()
        ]);
    }

    public function countByQuestionId(QuestionId $questionId): int
    {
        $sql = "SELECT COUNT(*) FROM question_topics WHERE question_id = :question_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['question_id' => $questionId->toString()]);
        
        return (int) $stmt->fetchColumn();
    }

    public function countByTopicId(TopicId $topicId): int
    {
        $sql = "SELECT COUNT(*) FROM question_topics WHERE topic_id = :topic_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['topic_id' => $topicId->toString()]);
        
        return (int) $stmt->fetchColumn();
    }
} 