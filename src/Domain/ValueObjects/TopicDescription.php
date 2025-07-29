<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class TopicDescription
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Topic description cannot be empty');
        }

        if (strlen($value) > 1000) {
            throw new \InvalidArgumentException('Topic description cannot exceed 1000 characters');
        }

        $this->value = trim($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(TopicDescription $other): bool
    {
        return $this->value === $other->value;
    }
} 