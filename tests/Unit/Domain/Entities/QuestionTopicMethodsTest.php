<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Question;
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicId;
use PHPUnit\Framework\TestCase;

class QuestionTopicMethodsTest extends TestCase
{
    private Question $question;
    private TopicId $topicId1;
    private TopicId $topicId2;

    protected function setUp(): void
    {
        $this->question = new Question(
            new QuestionText("Test question?"),
            new QuestionType("multiple_choice"),
            new ExamId("test-exam-id"),
            ["Option A", "Option B", "Option C", "Option D"],
            2,
            5
        );

        $this->topicId1 = TopicId::generate();
        $this->topicId2 = TopicId::generate();
    }

    public function testGetTopicIdsReturnsEmptyArrayInitially(): void
    {
        $this->assertEmpty($this->question->getTopicIds());
    }

    public function testAddTopicIdAddsTopicToQuestion(): void
    {
        $this->question->addTopicId($this->topicId1);
        
        $topicIds = $this->question->getTopicIds();
        $this->assertCount(1, $topicIds);
        $this->assertContains($this->topicId1->toString(), $topicIds);
    }

    public function testAddTopicIdDoesNotAddDuplicate(): void
    {
        $this->question->addTopicId($this->topicId1);
        $this->question->addTopicId($this->topicId1);
        
        $topicIds = $this->question->getTopicIds();
        $this->assertCount(1, $topicIds);
        $this->assertContains($this->topicId1->toString(), $topicIds);
    }

    public function testAddTopicIdUpdatesUpdatedAt(): void
    {
        $originalUpdatedAt = $this->question->getUpdatedAt();
        
        $this->question->addTopicId($this->topicId1);
        
        $this->assertNotEquals($originalUpdatedAt, $this->question->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->question->getUpdatedAt());
    }

    public function testRemoveTopicIdRemovesTopicFromQuestion(): void
    {
        $this->question->addTopicId($this->topicId1);
        $this->question->addTopicId($this->topicId2);
        
        $this->question->removeTopicId($this->topicId1);
        
        $topicIds = $this->question->getTopicIds();
        $this->assertCount(1, $topicIds);
        $this->assertContains($this->topicId2->toString(), $topicIds);
        $this->assertNotContains($this->topicId1->toString(), $topicIds);
    }

    public function testRemoveTopicIdUpdatesUpdatedAt(): void
    {
        $this->question->addTopicId($this->topicId1);
        $originalUpdatedAt = $this->question->getUpdatedAt();
        
        $this->question->removeTopicId($this->topicId1);
        
        $this->assertNotEquals($originalUpdatedAt, $this->question->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->question->getUpdatedAt());
    }

    public function testRemoveTopicIdDoesNothingIfTopicNotPresent(): void
    {
        $this->question->addTopicId($this->topicId1);
        $originalTopicIds = $this->question->getTopicIds();
        
        $this->question->removeTopicId($this->topicId2);
        
        $this->assertEquals($originalTopicIds, $this->question->getTopicIds());
    }

    public function testHasTopicIdReturnsTrueWhenTopicExists(): void
    {
        $this->question->addTopicId($this->topicId1);
        
        $this->assertTrue($this->question->hasTopicId($this->topicId1));
    }

    public function testHasTopicIdReturnsFalseWhenTopicDoesNotExist(): void
    {
        $this->question->addTopicId($this->topicId1);
        
        $this->assertFalse($this->question->hasTopicId($this->topicId2));
    }

    public function testSetTopicIdsReplacesAllTopics(): void
    {
        $this->question->addTopicId($this->topicId1);
        
        $this->question->setTopicIds([$this->topicId2]);
        
        $topicIds = $this->question->getTopicIds();
        $this->assertCount(1, $topicIds);
        $this->assertContains($this->topicId2->toString(), $topicIds);
        $this->assertNotContains($this->topicId1->toString(), $topicIds);
    }

    public function testSetTopicIdsWithMultipleTopics(): void
    {
        $this->question->setTopicIds([$this->topicId1, $this->topicId2]);
        
        $topicIds = $this->question->getTopicIds();
        $this->assertCount(2, $topicIds);
        $this->assertContains($this->topicId1->toString(), $topicIds);
        $this->assertContains($this->topicId2->toString(), $topicIds);
    }

    public function testSetTopicIdsUpdatesUpdatedAt(): void
    {
        $originalUpdatedAt = $this->question->getUpdatedAt();
        
        $this->question->setTopicIds([$this->topicId1]);
        
        $this->assertNotEquals($originalUpdatedAt, $this->question->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->question->getUpdatedAt());
    }

    public function testClearTopicIdsRemovesAllTopics(): void
    {
        $this->question->addTopicId($this->topicId1);
        $this->question->addTopicId($this->topicId2);
        
        $this->question->clearTopicIds();
        
        $this->assertEmpty($this->question->getTopicIds());
    }

    public function testClearTopicIdsUpdatesUpdatedAt(): void
    {
        $this->question->addTopicId($this->topicId1);
        $originalUpdatedAt = $this->question->getUpdatedAt();
        
        $this->question->clearTopicIds();
        
        $this->assertNotEquals($originalUpdatedAt, $this->question->getUpdatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->question->getUpdatedAt());
    }

    public function testMultipleTopicOperations(): void
    {
        // Add multiple topics
        $this->question->addTopicId($this->topicId1);
        $this->question->addTopicId($this->topicId2);
        
        $this->assertCount(2, $this->question->getTopicIds());
        $this->assertTrue($this->question->hasTopicId($this->topicId1));
        $this->assertTrue($this->question->hasTopicId($this->topicId2));
        
        // Remove one topic
        $this->question->removeTopicId($this->topicId1);
        
        $this->assertCount(1, $this->question->getTopicIds());
        $this->assertFalse($this->question->hasTopicId($this->topicId1));
        $this->assertTrue($this->question->hasTopicId($this->topicId2));
        
        // Clear all topics
        $this->question->clearTopicIds();
        
        $this->assertEmpty($this->question->getTopicIds());
        $this->assertFalse($this->question->hasTopicId($this->topicId1));
        $this->assertFalse($this->question->hasTopicId($this->topicId2));
    }
} 