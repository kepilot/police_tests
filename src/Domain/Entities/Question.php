<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\ExamId;
use DateTimeImmutable;

final class Question
{
    private QuestionId $id;
    private QuestionText $text;
    private QuestionType $type;
    private ExamId $examId;
    private array $options;
    private int $correctOption;
    private int $points;
    private bool $isActive;
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        QuestionText $text,
        QuestionType $type,
        ExamId $examId,
        array $options,
        int $correctOption,
        int $points = 1
    ) {
        $this->id = QuestionId::generate();
        $this->text = $text;
        $this->type = $type;
        $this->examId = $examId;
        $this->options = $options;
        $this->correctOption = $correctOption;
        $this->points = $points;
        $this->isActive = true;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): QuestionId
    {
        return $this->id;
    }

    public function getText(): QuestionText
    {
        return $this->text;
    }

    public function getType(): QuestionType
    {
        return $this->type;
    }

    public function getExamId(): ExamId
    {
        return $this->examId;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getCorrectOption(): int
    {
        return $this->correctOption;
    }

    public function getPoints(): int
    {
        return $this->points;
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
        QuestionText $text,
        array $options,
        int $correctOption,
        int $points
    ): void {
        $this->text = $text;
        $this->options = $options;
        $this->correctOption = $correctOption;
        $this->points = $points;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isCorrect(int $selectedOption): bool
    {
        return $selectedOption === $this->correctOption;
    }

    public function getScore(int $selectedOption): int
    {
        return $this->isCorrect($selectedOption) ? $this->points : 0;
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