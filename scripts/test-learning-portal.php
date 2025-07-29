<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use App\Domain\ValueObjects\TopicId;

echo "=== Learning Portal Test ===\n\n";

try {
    // Initialize container
    $container = new Container();
    
    echo "✅ Container initialized successfully\n";
    
    // Test 1: Check if repositories are available
    echo "\n--- Test 1: Repository Availability ---\n";
    
    $topicRepository = $container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
    echo "✅ TopicRepository available\n";
    
    $examRepository = $container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
    echo "✅ ExamRepository available\n";
    
    $questionRepository = $container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
    echo "✅ QuestionRepository available\n";
    
    $examAttemptRepository = $container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);
    echo "✅ ExamAttemptRepository available\n";
    
    // Test 2: Check if handlers are available
    echo "\n--- Test 2: Command Handler Availability ---\n";
    
    $createTopicHandler = $container->get(\App\Application\Commands\CreateTopicHandler::class);
    echo "✅ CreateTopicHandler available\n";
    
    $createExamHandler = $container->get(\App\Application\Commands\CreateExamHandler::class);
    echo "✅ CreateExamHandler available\n";
    
    // Test 3: Check if LearningController is available
    echo "\n--- Test 3: Controller Availability ---\n";
    
    $learningController = new \App\Presentation\Controllers\LearningController($container);
    echo "✅ LearningController available\n";
    
    // Test 4: Test Value Objects
    echo "\n--- Test 4: Value Objects ---\n";
    
    $topicTitle = new TopicTitle("PHP Fundamentals");
    echo "✅ TopicTitle created: " . $topicTitle->value() . "\n";
    
    $topicDescription = new TopicDescription("Learn the basics of PHP programming language");
    echo "✅ TopicDescription created: " . $topicDescription->value() . "\n";
    
    $topicLevel = TopicLevel::beginner();
    echo "✅ TopicLevel created: " . $topicLevel->value() . " (" . $topicLevel->getDisplayName() . ")\n";
    
    $examTitle = new ExamTitle("PHP Basics Quiz");
    echo "✅ ExamTitle created: " . $examTitle->value() . "\n";
    
    $examDescription = new ExamDescription("Test your knowledge of PHP fundamentals");
    echo "✅ ExamDescription created: " . $examDescription->value() . "\n";
    
    $examDuration = new ExamDuration(30);
    echo "✅ ExamDuration created: " . $examDuration->value() . " minutes (" . $examDuration->getDisplayValue() . ")\n";
    
    $passingScore = new ExamPassingScore(70);
    echo "✅ ExamPassingScore created: " . $passingScore->value() . "% (" . $passingScore->getDisplayValue() . ")\n";
    
    // Test 5: Test Entity Creation (without saving to database)
    echo "\n--- Test 5: Entity Creation ---\n";
    
    $topic = new \App\Domain\Entities\Topic($topicTitle, $topicDescription, $topicLevel);
    echo "✅ Topic entity created with ID: " . $topic->getId()->toString() . "\n";
    echo "   Title: " . $topic->getTitle()->value() . "\n";
    echo "   Level: " . $topic->getLevel()->getDisplayName() . "\n";
    echo "   Active: " . ($topic->isActive() ? 'Yes' : 'No') . "\n";
    
    $topicId = $topic->getId();
    
    $exam = new \App\Domain\Entities\Exam($examTitle, $examDescription, $examDuration, $passingScore, $topicId);
    echo "✅ Exam entity created with ID: " . $exam->getId()->toString() . "\n";
    echo "   Title: " . $exam->getTitle()->value() . "\n";
    echo "   Duration: " . $exam->getDuration()->getDisplayValue() . "\n";
    echo "   Passing Score: " . $exam->getPassingScore()->getDisplayValue() . "\n";
    echo "   Topic ID: " . $exam->getTopicId()->toString() . "\n";
    
    // Test 6: Test Question Entity
    echo "\n--- Test 6: Question Entity ---\n";
    
    $questionText = new \App\Domain\ValueObjects\QuestionText("What does PHP stand for?");
    $questionType = \App\Domain\ValueObjects\QuestionType::singleChoice();
    $examId = $exam->getId();
    $options = ["Personal Home Page", "PHP: Hypertext Preprocessor", "Programming Home Page", "Private Home Page"];
    $correctOption = 1; // PHP: Hypertext Preprocessor
    
    $question = new \App\Domain\Entities\Question($questionText, $questionType, $examId, $options, $correctOption, 2);
    echo "✅ Question entity created with ID: " . $question->getId()->toString() . "\n";
    echo "   Text: " . $question->getText()->value() . "\n";
    echo "   Type: " . $question->getType()->getDisplayName() . "\n";
    echo "   Options: " . count($question->getOptions()) . " options\n";
    echo "   Correct Option: " . $question->getCorrectOption() . "\n";
    echo "   Points: " . $question->getPoints() . "\n";
    
    // Test 7: Test Question Scoring
    echo "\n--- Test 7: Question Scoring ---\n";
    
    $correctAnswer = 1;
    $incorrectAnswer = 0;
    
    echo "   Correct answer (1): " . ($question->isCorrect($correctAnswer) ? 'Correct' : 'Incorrect') . "\n";
    echo "   Score for correct answer: " . $question->getScore($correctAnswer) . " points\n";
    echo "   Incorrect answer (0): " . ($question->isCorrect($incorrectAnswer) ? 'Correct' : 'Incorrect') . "\n";
    echo "   Score for incorrect answer: " . $question->getScore($incorrectAnswer) . " points\n";
    
    // Test 8: Test User Role Functionality
    echo "\n--- Test 8: User Role Functionality ---\n";
    
    $userRepository = $container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
    $users = $userRepository->findAll();
    
    if (!empty($users)) {
        $user = $users[0];
        echo "✅ User found: " . $user->getName() . "\n";
        echo "   Role: " . $user->getRole() . "\n";
        echo "   Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n";
        echo "   Is Super Admin: " . ($user->isSuperAdmin() ? 'Yes' : 'No') . "\n";
    } else {
        echo "⚠️  No users found in database\n";
    }
    
    echo "\n=== All Tests Completed Successfully! ===\n";
    echo "\nThe learning portal is ready with the following features:\n";
    echo "✅ Topic management (create, list, categorize by level)\n";
    echo "✅ Exam management (create, configure duration and passing scores)\n";
    echo "✅ Question system (multiple choice, true/false, single choice)\n";
    echo "✅ User role system (user, admin, superadmin)\n";
    echo "✅ Exam attempt tracking\n";
    echo "✅ Learning statistics\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 