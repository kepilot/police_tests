<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\ExamAttemptId;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\ExamScore;
use DateTimeImmutable;

final class ExamAttempt
{
    private ExamAttemptId $id;
    private UserId $userId;
    private ExamId $examId;
    private ExamScore $score;
    private bool $passed;
    private DateTimeImmutable $startedAt;
    private ?DateTimeImmutable $completedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        UserId $userId,
        ExamId $examId
    ) {
        $this->id = ExamAttemptId::generate();
        $this->userId = $userId;
        $this->examId = $examId;
        $this->score = new ExamScore(0);
        $this->passed = false;
        $this->startedAt = new DateTimeImmutable();
        $this->completedAt = null;
        $this->deletedAt = null;
    }

    public function getId(): ExamAttemptId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getExamId(): ExamId
    {
        return $this->examId;
    }

    public function getScore(): ExamScore
    {
        return $this->score;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getStartedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function complete(ExamScore $score, bool $passed): void
    {
        $this->score = $score;
        $this->passed = $passed;
        $this->completedAt = new DateTimeImmutable();
    }

    public function getDuration(): ?int
    {
        if (!$this->completedAt) {
            return null;
        }

        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    public function delete(): void
    {
        $this->deletedAt = new DateTimeImmutable();
    }
} 