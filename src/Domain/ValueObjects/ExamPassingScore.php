<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class ExamPassingScore
{
    private int $value; // Percentage (0-100)

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Passing score must be between 0 and 100');
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(ExamPassingScore $other): bool
    {
        return $this->value === $other->value;
    }

    public function isPassing(int $score): bool
    {
        return $score >= $this->value;
    }

    public function getDisplayValue(): string
    {
        return $this->value . '%';
    }
} 