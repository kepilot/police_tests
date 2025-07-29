<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class TopicTitle
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('Topic title cannot be empty');
        }

        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Topic title cannot exceed 255 characters');
        }

        $this->value = trim($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(TopicTitle $other): bool
    {
        return $this->value === $other->value;
    }
} 