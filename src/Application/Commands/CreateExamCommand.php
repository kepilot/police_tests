<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use App\Domain\ValueObjects\TopicId;

final class CreateExamCommand
{
    public function __construct(
        private readonly ExamTitle $title,
        private readonly ExamDescription $description,
        private readonly ExamDuration $duration,
        private readonly ExamPassingScore $passingScore,
        private readonly TopicId $topicId
    ) {
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
} 