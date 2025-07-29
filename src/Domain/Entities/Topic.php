<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use DateTimeImmutable;

final class Topic
{
    private TopicId $id;
    private TopicTitle $title;
    private TopicDescription $description;
    private TopicLevel $level;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        TopicTitle $title,
        TopicDescription $description,
        TopicLevel $level
    ) {
        $this->id = TopicId::generate();
        $this->title = $title;
        $this->description = $description;
        $this->level = $level;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): TopicId
    {
        return $this->id;
    }

    public function getTitle(): TopicTitle
    {
        return $this->title;
    }

    public function getDescription(): TopicDescription
    {
        return $this->description;
    }

    public function getLevel(): TopicLevel
    {
        return $this->level;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function update(
        TopicTitle $title,
        TopicDescription $description,
        TopicLevel $level
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->level = $level;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
        $this->isActive = false;
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->isActive = true;
        $this->updatedAt = new DateTimeImmutable();
    }
} 