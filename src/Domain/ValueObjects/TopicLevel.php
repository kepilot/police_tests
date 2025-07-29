<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

final class TopicLevel
{
    public const BEGINNER = 'beginner';
    public const INTERMEDIATE = 'intermediate';
    public const ADVANCED = 'advanced';
    public const EXPERT = 'expert';

    private string $value;

    public function __construct(string $value)
    {
        $validLevels = [
            self::BEGINNER,
            self::INTERMEDIATE,
            self::ADVANCED,
            self::EXPERT
        ];

        if (!in_array($value, $validLevels, true)) {
            throw new \InvalidArgumentException(
                'Invalid topic level. Must be one of: ' . implode(', ', $validLevels)
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(TopicLevel $other): bool
    {
        return $this->value === $other->value;
    }

    public static function beginner(): self
    {
        return new self(self::BEGINNER);
    }

    public static function intermediate(): self
    {
        return new self(self::INTERMEDIATE);
    }

    public static function advanced(): self
    {
        return new self(self::ADVANCED);
    }

    public static function expert(): self
    {
        return new self(self::EXPERT);
    }

    public function getDisplayName(): string
    {
        return ucfirst($this->value);
    }
} 