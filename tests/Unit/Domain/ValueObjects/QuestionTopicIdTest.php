<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use App\Domain\ValueObjects\QuestionTopicId;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class QuestionTopicIdTest extends TestCase
{
    public function testGenerateCreatesValidUuid(): void
    {
        $id = QuestionTopicId::generate();
        
        $this->assertInstanceOf(QuestionTopicId::class, $id);
        $this->assertNotEmpty($id->toString());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id->toString());
    }

    public function testFromStringWithValidUuid(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = QuestionTopicId::fromString($validUuid);
        
        $this->assertInstanceOf(QuestionTopicId::class, $id);
        $this->assertEquals($validUuid, $id->toString());
    }

    public function testFromStringWithInvalidUuidThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid QuestionTopicId format');
        
        QuestionTopicId::fromString('invalid-uuid');
    }

    public function testEqualsReturnsTrueForSameId(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id1 = QuestionTopicId::fromString($uuid);
        $id2 = QuestionTopicId::fromString($uuid);
        
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsReturnsFalseForDifferentIds(): void
    {
        $id1 = QuestionTopicId::fromString('550e8400-e29b-41d4-a716-446655440000');
        $id2 = QuestionTopicId::fromString('550e8400-e29b-41d4-a716-446655440001');
        
        $this->assertFalse($id1->equals($id2));
    }

    public function testToStringReturnsUuidString(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = QuestionTopicId::fromString($uuid);
        
        $this->assertEquals($uuid, $id->toString());
    }

    public function testMagicToStringReturnsUuidString(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = QuestionTopicId::fromString($uuid);
        
        $this->assertEquals($uuid, (string) $id);
    }
} 