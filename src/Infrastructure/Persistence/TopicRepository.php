<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Topic;
use App\Domain\Repositories\TopicRepositoryInterface;
use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use PDO;

final class TopicRepository implements TopicRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Topic $topic): void
    {
        $sql = "INSERT INTO topics (id, title, description, level, is_active, created_at, updated_at, deleted_at) 
                VALUES (:id, :title, :description, :level, :is_active, :created_at, :updated_at, :deleted_at)
                ON DUPLICATE KEY UPDATE 
                title = :title, 
                description = :description, 
                level = :level, 
                is_active = :is_active, 
                updated_at = :updated_at, 
                deleted_at = :deleted_at";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $topic->getId()->toString(),
            'title' => $topic->getTitle()->value(),
            'description' => $topic->getDescription()->value(),
            'level' => $topic->getLevel()->value(),
            'is_active' => $topic->isActive(),
            'created_at' => $topic->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $topic->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'deleted_at' => $topic->getDeletedAt()?->format('Y-m-d H:i:s')
        ]);
    }

    public function findById(TopicId $id): ?Topic
    {
        $sql = "SELECT * FROM topics WHERE id = :id AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }

        return $this->createTopicFromRow($row);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM topics WHERE deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $topics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $topics[] = $this->createTopicFromRow($row);
        }

        return $topics;
    }

    public function findByLevel(TopicLevel $level): array
    {
        $sql = "SELECT * FROM topics WHERE level = :level AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['level' => $level->value()]);
        
        $topics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $topics[] = $this->createTopicFromRow($row);
        }

        return $topics;
    }

    public function findActive(): array
    {
        $sql = "SELECT * FROM topics WHERE is_active = 1 AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->pdo->query($sql);
        
        $topics = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $topics[] = $this->createTopicFromRow($row);
        }

        return $topics;
    }

    public function delete(TopicId $id): void
    {
        $sql = "UPDATE topics SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id->toString()]);
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM topics WHERE deleted_at IS NULL";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    public function countByLevel(TopicLevel $level): int
    {
        $sql = "SELECT COUNT(*) FROM topics WHERE level = :level AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['level' => $level->value()]);
        return (int) $stmt->fetchColumn();
    }

    private function createTopicFromRow(array $row): Topic
    {
        $topic = new Topic(
            new TopicTitle($row['title']),
            new TopicDescription($row['description']),
            new TopicLevel($row['level'])
        );

        // Use reflection to set the ID and other properties
        $reflection = new \ReflectionClass($topic);
        
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($topic, TopicId::fromString($row['id']));

        $isActiveProperty = $reflection->getProperty('isActive');
        $isActiveProperty->setAccessible(true);
        $isActiveProperty->setValue($topic, (bool) $row['is_active']);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($topic, new \DateTimeImmutable($row['created_at']));

        if ($row['updated_at']) {
            $updatedAtProperty = $reflection->getProperty('updatedAt');
            $updatedAtProperty->setAccessible(true);
            $updatedAtProperty->setValue($topic, new \DateTimeImmutable($row['updated_at']));
        }

        if ($row['deleted_at']) {
            $deletedAtProperty = $reflection->getProperty('deletedAt');
            $deletedAtProperty->setAccessible(true);
            $deletedAtProperty->setValue($topic, new \DateTimeImmutable($row['deleted_at']));
        }

        return $topic;
    }
} 