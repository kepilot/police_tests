<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class ExamScore
{
    private int $value; // Score as points

    public function __construct(int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Exam score cannot be negative');
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(ExamScore $other): bool
    {
        return $this->value === $other->value;
    }

    public function add(ExamScore $other): self
    {
        return new self($this->value + $other->value);
    }

    public function getPercentage(int $totalPoints): float
    {
        if ($totalPoints <= 0) {
            return 0.0;
        }

        return round(($this->value / $totalPoints) * 100, 2);
    }

    public function getDisplayValue(): string
    {
        return $this->value . ' points';
    }
} 