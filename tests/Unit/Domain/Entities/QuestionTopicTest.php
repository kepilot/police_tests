<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\QuestionTopic;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;
use PHPUnit\Framework\TestCase;

class QuestionTopicTest extends TestCase
{
    public function testConstructorCreatesValidQuestionTopic(): void
    {
        $questionId = QuestionId::generate();
        $topicId = TopicId::generate();
        
        $questionTopic = new QuestionTopic($questionId, $topicId);
        
        $this->assertInstanceOf(QuestionTopic::class, $questionTopic);
        $this->assertTrue($questionTopic->getQuestionId()->equals($questionId));
        $this->assertTrue($questionTopic->getTopicId()->equals($topicId));
        $this->assertInstanceOf(\DateTimeImmutable::class, $questionTopic->getCreatedAt());
    }

    public function testGetIdReturnsValidId(): void
    {
        $questionId = QuestionId::generate();
        $topicId = TopicId::generate();
        
        $questionTopic = new QuestionTopic($questionId, $topicId);
        
        $this->assertNotEmpty($questionTopic->getId()->toString());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $questionTopic->getId()->toString());
    }

    public function testGetQuestionIdReturnsCorrectQuestionId(): void
    {
        $questionId = QuestionId::generate();
        $topicId = TopicId::generate();
        
        $questionTopic = new QuestionTopic($questionId, $topicId);
        
        $this->assertTrue($questionTopic->getQuestionId()->equals($questionId));
    }

    public function testGetTopicIdReturnsCorrectTopicId(): void
    {
        $questionId = QuestionId::generate();
        $topicId = TopicId::generate();
        
        $questionTopic = new QuestionTopic($questionId, $topicId);
        
        $this->assertTrue($questionTopic->getTopicId()->equals($topicId));
    }

    public function testGetCreatedAtReturnsValidDateTime(): void
    {
        $questionId = QuestionId::generate();
        $topicId = TopicId::generate();
        
        $questionTopic = new QuestionTopic($questionId, $topicId);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $questionTopic->getCreatedAt());
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $questionTopic->getCreatedAt());
    }

    public function testCreatedAtIsSetToCurrentTime(): void
    {
        $beforeCreation = new \DateTimeImmutable();
        usleep(1000); // Small delay to ensure different timestamps
        
        $questionId = QuestionId::generate();
        $topicId = TopicId::generate();
        $questionTopic = new QuestionTopic($questionId, $topicId);
        
        usleep(1000); // Small delay to ensure different timestamps
        $afterCreation = new \DateTimeImmutable();
        
        $this->assertGreaterThanOrEqual($beforeCreation, $questionTopic->getCreatedAt());
        $this->assertLessThanOrEqual($afterCreation, $questionTopic->getCreatedAt());
    }
} 