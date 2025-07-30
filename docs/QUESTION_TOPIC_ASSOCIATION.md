# Question-Topic Association

This document describes the implementation of the many-to-many relationship between questions and topics, allowing questions to be associated with 0 or more topics.

## Overview

The system now supports associating questions with multiple topics, enabling better organization and categorization of learning content. A question can be associated with:
- 0 topics (no association)
- 1 topic
- Multiple topics

## Database Schema

### New Table: `question_topics`

```sql
CREATE TABLE question_topics (
    id VARCHAR(36) PRIMARY KEY,
    question_id VARCHAR(36) NOT NULL,
    topic_id VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    UNIQUE KEY unique_question_topic (question_id, topic_id)
);
```

This table implements a many-to-many relationship with:
- Composite unique constraint to prevent duplicate associations
- Cascade delete to maintain referential integrity
- Indexes for efficient querying

## Domain Model

### New Value Objects

- `QuestionTopicId`: Represents the unique identifier for question-topic associations

### New Entities

- `QuestionTopic`: Represents the many-to-many relationship between questions and topics

### Updated Entities

#### Question Entity

The `Question` entity has been enhanced with topic management methods:

```php
// Get all topic IDs associated with this question
public function getTopicIds(): array

// Add a topic association
public function addTopicId(TopicId $topicId): void

// Remove a topic association
public function removeTopicId(TopicId $topicId): void

// Check if question has a specific topic
public function hasTopicId(TopicId $topicId): bool

// Set multiple topic associations (replaces existing)
public function setTopicIds(array $topicIds): void

// Remove all topic associations
public function clearTopicIds(): void
```

## Application Layer

### Services

#### QuestionTopicService

The main service for managing question-topic associations:

```php
// Associate a question with a topic
public function associateQuestionWithTopic(QuestionId $questionId, TopicId $topicId): void

// Disassociate a question from a topic
public function disassociateQuestionFromTopic(QuestionId $questionId, TopicId $topicId): void

// Get all topics for a question
public function getTopicsForQuestion(QuestionId $questionId): array

// Get active topics for a question
public function getActiveTopicsForQuestion(QuestionId $questionId): array

// Get all questions for a topic
public function getQuestionsForTopic(TopicId $topicId): array

// Get active questions for a topic
public function getActiveQuestionsForTopic(TopicId $topicId): array

// Set multiple topics for a question (replaces existing)
public function setQuestionTopics(QuestionId $questionId, array $topicIds): void

// Clear all topics for a question
public function clearQuestionTopics(QuestionId $questionId): void

// Get count of topics for a question
public function getQuestionTopicCount(QuestionId $questionId): int

// Get count of questions for a topic
public function getTopicQuestionCount(TopicId $topicId): int
```

### Commands and Handlers

#### AssociateQuestionWithTopicCommand
Associates a question with a specific topic.

#### DisassociateQuestionFromTopicCommand
Removes the association between a question and a topic.

#### SetQuestionTopicsCommand
Sets multiple topic associations for a question, replacing any existing associations.

## Infrastructure Layer

### Repositories

#### QuestionTopicRepositoryInterface & QuestionTopicRepository

Manages the persistence of question-topic associations:

```php
public function save(QuestionTopic $questionTopic): void
public function findByQuestionId(QuestionId $questionId): array
public function findByTopicId(TopicId $topicId): array
public function findByQuestionIdAndTopicId(QuestionId $questionId, TopicId $topicId): ?QuestionTopic
public function deleteByQuestionId(QuestionId $questionId): void
public function deleteByTopicId(TopicId $topicId): void
public function deleteByQuestionIdAndTopicId(QuestionId $questionId, TopicId $topicId): void
public function countByQuestionId(QuestionId $questionId): int
public function countByTopicId(TopicId $topicId): int
```

#### Updated Repository Interfaces

Both `QuestionRepositoryInterface` and `TopicRepositoryInterface` have been extended with methods to query the many-to-many relationship:

**QuestionRepositoryInterface:**
```php
public function findByTopicId(TopicId $topicId): array
public function findActiveByTopicId(TopicId $topicId): array
```

**TopicRepositoryInterface:**
```php
public function findByQuestionId(QuestionId $questionId): array
public function findActiveByQuestionId(QuestionId $questionId): array
```

## Usage Examples

### Basic Association

```php
// Get services from container
$questionTopicService = $container->get(QuestionTopicService::class);

// Associate a question with a topic
$questionTopicService->associateQuestionWithTopic($questionId, $topicId);

// Get all topics for a question
$topics = $questionTopicService->getTopicsForQuestion($questionId);

// Get all questions for a topic
$questions = $questionTopicService->getQuestionsForTopic($topicId);
```

### Using Commands

```php
// Associate using command
$command = new AssociateQuestionWithTopicCommand($questionId, $topicId);
$handler = $container->get(AssociateQuestionWithTopicHandler::class);
$handler->handle($command);

// Set multiple topics
$command = new SetQuestionTopicsCommand($questionId, [$topicId1, $topicId2, $topicId3]);
$handler = $container->get(SetQuestionTopicsHandler::class);
$handler->handle($command);
```

### Entity Operations

```php
// Add topics to a question
$question->addTopicId($topicId1);
$question->addTopicId($topicId2);

// Check if question has a topic
if ($question->hasTopicId($topicId)) {
    // Do something
}

// Remove a topic
$question->removeTopicId($topicId);

// Set multiple topics (replaces existing)
$question->setTopicIds([$topicId1, $topicId2]);

// Clear all topics
$question->clearTopicIds();
```

## Testing

### Unit Tests

- `QuestionTopicIdTest`: Tests the value object
- `QuestionTopicTest`: Tests the entity
- `QuestionTopicMethodsTest`: Tests the Question entity's topic methods

### Integration Test

- `test-question-topics.php`: Comprehensive test script demonstrating all functionality

## Migration

To apply the database changes, run the migration:

```sql
-- Run migration 007_create_question_topics_table.sql
```

## Benefits

1. **Flexible Categorization**: Questions can belong to multiple topics
2. **Better Organization**: Improved content management and discovery
3. **Scalable Design**: Easy to add/remove topic associations
4. **Data Integrity**: Proper foreign key constraints and unique constraints
5. **Performance**: Optimized with appropriate indexes
6. **Domain-Driven**: Follows DDD principles with proper value objects and entities

## Future Enhancements

Potential improvements could include:
- Topic hierarchies (parent-child relationships)
- Topic weights or priorities for questions
- Bulk operations for topic associations
- Topic-based question filtering and search
- Analytics on topic-question relationships 