<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use App\Domain\ValueObjects\TopicId;
use DateTimeImmutable;

final class Exam
{
    private ExamId $id;
    private ExamTitle $title;
    private ExamDescription $description;
    private ExamDuration $duration;
    private ExamPassingScore $passingScore;
    private TopicId $topicId;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        ExamTitle $title,
        ExamDescription $description,
        ExamDuration $duration,
        ExamPassingScore $passingScore,
        TopicId $topicId
    ) {
        $this->id = ExamId::generate();
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;
        $this->passingScore = $passingScore;
        $this->topicId = $topicId;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): ExamId
    {
        return $this->id;
    }

    public function getTitle(): ExamTitle
    {
        return $this->title;
    }

    public function getDescription(): ExamDescription
    {
        return $this->description;
    }

    public function getDuration(): ExamDuration
    {
        return $this->duration;
    }

    public function getPassingScore(): ExamPassingScore
    {
        return $this->passingScore;
    }

    public function getTopicId(): TopicId
    {
        return $this->topicId;
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
        ExamTitle $title,
        ExamDescription $description,
        ExamDuration $duration,
        ExamPassingScore $passingScore
    ): void {
        $this->title = $title;
        $this->description = $description;
        $this->duration = $duration;
        $this->passingScore = $passingScore;
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