<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;

final class CreateTopicCommand
{
    public function __construct(
        private readonly TopicTitle $title,
        private readonly TopicDescription $description,
        private readonly TopicLevel $level
    ) {
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
} 