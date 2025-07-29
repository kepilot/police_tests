<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class ExamTitle
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Exam title cannot be empty');
        }

        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Exam title cannot exceed 255 characters');
        }

        $this->value = trim($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ExamTitle $other): bool
    {
        return $this->value === $other->value;
    }
} 