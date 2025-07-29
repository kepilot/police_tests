<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class ExamDuration
{
    private int $value; // Duration in minutes

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Exam duration must be greater than 0 minutes');
        }

        if ($value > 480) { // 8 hours max
            throw new \InvalidArgumentException('Exam duration cannot exceed 480 minutes (8 hours)');
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(ExamDuration $other): bool
    {
        return $this->value === $other->value;
    }

    public function getDisplayValue(): string
    {
        if ($this->value < 60) {
            return $this->value . ' minutes';
        }

        $hours = floor($this->value / 60);
        $minutes = $this->value % 60;

        if ($minutes === 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        }

        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }

    public function getSeconds(): int
    {
        return $this->value * 60;
    }
} 