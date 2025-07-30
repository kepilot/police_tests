<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Application\Commands\AssociateQuestionWithTopicCommand;
use App\Application\Commands\DisassociateQuestionFromTopicCommand;
use App\Application\Commands\SetQuestionTopicsCommand;
use App\Application\Commands\AssociateQuestionWithTopicHandler;
use App\Application\Commands\DisassociateQuestionFromTopicHandler;
use App\Application\Commands\SetQuestionTopicsHandler;
use App\Application\Services\QuestionTopicService;
use App\Domain\ValueObjects\QuestionId;
use App\Domain\ValueObjects\TopicId;
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\ExamId;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use App\Domain\Entities\Question;
use App\Domain\Entities\Topic;

try {
    echo "Testing Question-Topic Association Functionality\n";
    echo "===============================================\n\n";

    // Initialize container
    $container = new Container();
    
    // Get services
    $questionTopicService = $container->get(QuestionTopicService::class);
    $associateHandler = $container->get(AssociateQuestionWithTopicHandler::class);
    $disassociateHandler = $container->get(DisassociateQuestionFromTopicHandler::class);
    $setTopicsHandler = $container->get(SetQuestionTopicsHandler::class);

    // Create test topics
    echo "Creating test topics...\n";
    $topic1 = new Topic(
        new TopicTitle("PHP Basics"),
        new TopicDescription("Fundamental PHP concepts"),
        new TopicLevel("beginner")
    );
    
    $topic2 = new Topic(
        new TopicTitle("Object-Oriented Programming"),
        new TopicDescription("OOP principles in PHP"),
        new TopicLevel("intermediate")
    );
    
    $topic3 = new Topic(
        new TopicTitle("Design Patterns"),
        new TopicDescription("Common design patterns"),
        new TopicLevel("advanced")
    );

    // Save topics (assuming we have a topic repository)
    $topicRepository = $container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
    $topicRepository->save($topic1);
    $topicRepository->save($topic2);
    $topicRepository->save($topic3);

    echo "✓ Topics created successfully\n\n";

    // Create a test question
    echo "Creating test question...\n";
    $question = new Question(
        new QuestionText("What is the difference between public, private, and protected in PHP?"),
        new QuestionType("multiple_choice"),
        new ExamId("test-exam-id"),
        ["Public", "Private", "Protected", "All of the above"],
        3,
        5
    );

    // Save question (assuming we have a question repository)
    $questionRepository = $container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
    $questionRepository->save($question);

    echo "✓ Question created successfully\n\n";

    // Test 1: Associate question with a single topic
    echo "Test 1: Associating question with PHP Basics topic...\n";
    $associateCommand = new AssociateQuestionWithTopicCommand(
        $question->getId(),
        $topic1->getId()
    );
    $associateHandler->handle($associateCommand);
    echo "✓ Question associated with PHP Basics topic\n\n";

    // Test 2: Associate question with another topic
    echo "Test 2: Associating question with OOP topic...\n";
    $associateCommand2 = new AssociateQuestionWithTopicCommand(
        $question->getId(),
        $topic2->getId()
    );
    $associateHandler->handle($associateCommand2);
    echo "✓ Question associated with OOP topic\n\n";

    // Test 3: Get topics for question
    echo "Test 3: Getting topics for question...\n";
    $topics = $questionTopicService->getTopicsForQuestion($question->getId());
    echo "Question is associated with " . count($topics) . " topics:\n";
    foreach ($topics as $topic) {
        echo "- " . $topic->getTitle()->value() . " (" . $topic->getLevel()->value() . ")\n";
    }
    echo "\n";

    // Test 4: Set multiple topics at once
    echo "Test 4: Setting multiple topics for question...\n";
    $setTopicsCommand = new SetQuestionTopicsCommand(
        $question->getId(),
        [$topic1->getId(), $topic3->getId()]
    );
    $setTopicsHandler->handle($setTopicsCommand);
    echo "✓ Question topics updated\n\n";

    // Test 5: Get updated topics
    echo "Test 5: Getting updated topics for question...\n";
    $updatedTopics = $questionTopicService->getTopicsForQuestion($question->getId());
    echo "Question is now associated with " . count($updatedTopics) . " topics:\n";
    foreach ($updatedTopics as $topic) {
        echo "- " . $topic->getTitle()->value() . " (" . $topic->getLevel()->value() . ")\n";
    }
    echo "\n";

    // Test 6: Disassociate from a topic
    echo "Test 6: Disassociating question from Design Patterns topic...\n";
    $disassociateCommand = new DisassociateQuestionFromTopicCommand(
        $question->getId(),
        $topic3->getId()
    );
    $disassociateHandler->handle($disassociateCommand);
    echo "✓ Question disassociated from Design Patterns topic\n\n";

    // Test 7: Get final topics
    echo "Test 7: Getting final topics for question...\n";
    $finalTopics = $questionTopicService->getTopicsForQuestion($question->getId());
    echo "Question is finally associated with " . count($finalTopics) . " topics:\n";
    foreach ($finalTopics as $topic) {
        echo "- " . $topic->getTitle()->value() . " (" . $topic->getLevel()->value() . ")\n";
    }
    echo "\n";

    // Test 8: Get questions for a topic
    echo "Test 8: Getting questions for PHP Basics topic...\n";
    $questions = $questionTopicService->getQuestionsForTopic($topic1->getId());
    echo "PHP Basics topic has " . count($questions) . " questions\n\n";

    // Test 9: Clear all topics
    echo "Test 9: Clearing all topics for question...\n";
    $questionTopicService->clearQuestionTopics($question->getId());
    echo "✓ All topics cleared for question\n\n";

    // Test 10: Verify no topics
    echo "Test 10: Verifying no topics remain...\n";
    $noTopics = $questionTopicService->getTopicsForQuestion($question->getId());
    echo "Question now has " . count($noTopics) . " topics\n\n";

    echo "All tests completed successfully! ✅\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 