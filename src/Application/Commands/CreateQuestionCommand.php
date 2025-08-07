<?php

declare(strict_types=1);

namespace App\Application\Commands;

use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\QuestionId;

final class CreateQuestionCommand
{
    public function __construct(
        public readonly QuestionText $text,
        public readonly QuestionType $type,
        public readonly array $options,
        public readonly int $correctOption,
        public readonly int $points,
        public readonly ?string $examId = null
    ) {
    }
} 