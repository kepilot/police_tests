<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class QuestionType
{
    public const MULTIPLE_CHOICE = 'multiple_choice';
    public const TRUE_FALSE = 'true_false';
    public const SINGLE_CHOICE = 'single_choice';

    private string $value;

    public function __construct(string $value)
    {
        $validTypes = [
            self::MULTIPLE_CHOICE,
            self::TRUE_FALSE,
            self::SINGLE_CHOICE
        ];

        if (!in_array($value, $validTypes, true)) {
            throw new \InvalidArgumentException(
                'Invalid question type. Must be one of: ' . implode(', ', $validTypes)
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(QuestionType $other): bool
    {
        return $this->value === $other->value;
    }

    public static function multipleChoice(): self
    {
        return new self(self::MULTIPLE_CHOICE);
    }

    public static function trueFalse(): self
    {
        return new self(self::TRUE_FALSE);
    }

    public static function singleChoice(): self
    {
        return new self(self::SINGLE_CHOICE);
    }

    public function getDisplayName(): string
    {
        return match($this->value) {
            self::MULTIPLE_CHOICE => 'Multiple Choice',
            self::TRUE_FALSE => 'True/False',
            self::SINGLE_CHOICE => 'Single Choice',
            default => 'Unknown'
        };
    }
} 