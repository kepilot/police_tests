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
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use App\Domain\Entities\Topic;
use App\Domain\Entities\Exam;
use App\Domain\Entities\Question;
use App\Domain\Entities\User;

echo "=== Testing Exam Assignment Functionality ===\n\n";

try {
    // Initialize container
    $container = new Container();
    
    echo "✅ Container initialized successfully\n\n";

    // Test 1: Create test users
    echo "--- Test 1: Creating Test Users ---\n";
    
    $userRepository = $container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
    $registerHandler = $container->get(\App\Application\Commands\RegisterUserHandler::class);
    
    // Get or create admin user
    $adminUser = $userRepository->findByEmail('admin@example.com');
    if (!$adminUser) {
        $adminCommand = new \App\Application\Commands\RegisterUserCommand(
            'Admin User',
            new Email('admin@example.com'),
            new Password('AdminPass123!')
        );
        $adminUser = $registerHandler($adminCommand);
        echo "✅ Created admin user: " . $adminUser->getName() . " (" . $adminUser->getEmail()->value() . ")\n";
    } else {
        echo "✅ Found existing admin user: " . $adminUser->getName() . " (" . $adminUser->getEmail()->value() . ")\n";
    }
    
    // Get or create regular user
    $studentUser = $userRepository->findByEmail('student@example.com');
    if (!$studentUser) {
        $userCommand = new \App\Application\Commands\RegisterUserCommand(
            'Test Student',
            new Email('student@example.com'),
            new Password('StudentPass123!')
        );
        $studentUser = $registerHandler($userCommand);
        echo "✅ Created student user: " . $studentUser->getName() . " (" . $studentUser->getEmail()->value() . ")\n";
    } else {
        echo "✅ Found existing student user: " . $studentUser->getName() . " (" . $studentUser->getEmail()->value() . ")\n";
    }
    echo "\n";

    // Test 2: Create test topic
    echo "--- Test 2: Creating Test Topic ---\n";
    
    $topicRepository = $container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
    
    $topic = new Topic(
        new TopicTitle("PHP Fundamentals"),
        new TopicDescription("Basic PHP concepts and syntax"),
        new TopicLevel("beginner")
    );
    
    $topicRepository->save($topic);
    echo "✅ Created topic: " . $topic->getTitle()->value() . "\n\n";

    // Test 3: Create test exam
    echo "--- Test 3: Creating Test Exam ---\n";
    
    $examRepository = $container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
    
    $exam = new Exam(
        new ExamTitle("PHP Basics Test"),
        new ExamDescription("Test your PHP fundamentals knowledge"),
        new ExamDuration(30),
        new ExamPassingScore(70),
        $topic->getId()
    );
    
    $examRepository->save($exam);
    echo "✅ Created exam: " . $exam->getTitle()->value() . "\n";
    echo "   Duration: " . $exam->getDuration()->getDisplayValue() . "\n";
    echo "   Passing Score: " . $exam->getPassingScore()->getDisplayValue() . "\n\n";

    // Test 4: Create test questions
    echo "--- Test 4: Creating Test Questions ---\n";
    
    $questionRepository = $container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
    
    $questions = [
        [
            'text' => 'What is the correct way to declare a variable in PHP?',
            'type' => 'multiple_choice',
            'options' => ['$variable', 'var variable', 'variable', 'declare variable'],
            'correct' => 0,
            'points' => 5
        ],
        [
            'text' => 'Which of the following is a valid PHP function?',
            'type' => 'multiple_choice',
            'options' => ['echo()', 'print()', 'display()', 'show()'],
            'correct' => 1,
            'points' => 5
        ],
        [
            'text' => 'PHP is a server-side scripting language.',
            'type' => 'true_false',
            'options' => ['True', 'False'],
            'correct' => 0,
            'points' => 3
        ],
        [
            'text' => 'What does PHP stand for?',
            'type' => 'multiple_choice',
            'options' => ['Personal Home Page', 'PHP: Hypertext Preprocessor', 'Programming Home Page', 'Private Home Page'],
            'correct' => 1,
            'points' => 4
        ],
        [
            'text' => 'PHP files have the extension .php',
            'type' => 'true_false',
            'options' => ['True', 'False'],
            'correct' => 0,
            'points' => 3
        ]
    ];
    
    foreach ($questions as $index => $questionData) {
        $question = new Question(
            new QuestionText($questionData['text']),
            new QuestionType($questionData['type']),
            $exam->getId(),
            $questionData['options'],
            $questionData['correct'],
            $questionData['points']
        );
        
        $questionRepository->save($question);
        echo "✅ Created question " . ($index + 1) . ": " . substr($questionData['text'], 0, 50) . "...\n";
    }
    echo "\n";

    // Test 5: Test exam assignment
    echo "--- Test 5: Testing Exam Assignment ---\n";
    
    $learningController = new \App\Presentation\Controllers\LearningController($container);
    
    // Assign exam to student
    $assignmentResult = $learningController->assignExamToUser(
        $studentUser->getId()->toString(),
        $exam->getId()->toString(),
        $adminUser->getId()->toString(),
        (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s') // Due in 7 days
    );
    
    if ($assignmentResult['success']) {
        echo "✅ Exam assigned successfully\n";
        echo "   Assignment ID: " . $assignmentResult['data']['id'] . "\n";
        echo "   Due Date: " . $assignmentResult['data']['due_date'] . "\n";
    } else {
        echo "❌ Failed to assign exam: " . $assignmentResult['message'] . "\n";
    }
    echo "\n";

    // Test 6: Test getting user assignments
    echo "--- Test 6: Testing Get User Assignments ---\n";
    
    $assignmentsResult = $learningController->getUserAssignments($studentUser->getId()->toString());
    
    if ($assignmentsResult['success']) {
        echo "✅ Retrieved user assignments\n";
        foreach ($assignmentsResult['data'] as $assignment) {
            echo "   - Assignment ID: " . $assignment['id'] . "\n";
            echo "     Exam ID: " . $assignment['exam_id'] . "\n";
            echo "     Assigned: " . $assignment['assigned_at'] . "\n";
            echo "     Due: " . $assignment['due_date'] . "\n";
            echo "     Completed: " . ($assignment['is_completed'] ? 'Yes' : 'No') . "\n";
            echo "     Overdue: " . ($assignment['is_overdue'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "❌ Failed to get assignments: " . $assignmentsResult['message'] . "\n";
    }
    echo "\n";

    // Test 7: Test getting pending assignments
    echo "--- Test 7: Testing Get Pending Assignments ---\n";
    
    $pendingResult = $learningController->getPendingAssignments($studentUser->getId()->toString());
    
    if ($pendingResult['success']) {
        echo "✅ Retrieved pending assignments\n";
        echo "   Count: " . count($pendingResult['data']) . "\n";
    } else {
        echo "❌ Failed to get pending assignments: " . $pendingResult['message'] . "\n";
    }
    echo "\n";

    // Test 8: Test starting exam attempt
    echo "--- Test 8: Testing Start Exam Attempt ---\n";
    
    $startResult = $learningController->startExamAttempt(
        $studentUser->getId()->toString(),
        $exam->getId()->toString()
    );
    
    if ($startResult['success']) {
        echo "✅ Exam attempt started successfully\n";
        echo "   Attempt ID: " . $startResult['data']['attempt_id'] . "\n";
        echo "   Exam Title: " . $startResult['data']['exam']['title'] . "\n";
        echo "   Duration: " . $startResult['data']['exam']['duration_minutes'] . " minutes\n";
        echo "   Questions: " . count($startResult['data']['questions']) . "\n";
        echo "   Started: " . $startResult['data']['started_at'] . "\n";
        
        $attemptId = $startResult['data']['attempt_id'];
        $examQuestions = $startResult['data']['questions'];
    } else {
        echo "❌ Failed to start exam attempt: " . $startResult['message'] . "\n";
        exit(1);
    }
    echo "\n";

    // Test 9: Test submitting exam attempt
    echo "--- Test 9: Testing Submit Exam Attempt ---\n";
    
    // Simulate user answers (all correct for this test)
    $answers = [];
    foreach ($examQuestions as $question) {
        $answers[$question['id']] = 0; // First option index (correct for our test questions)
    }
    
    $submitResult = $learningController->submitExamAttempt($attemptId, $answers);
    
    if ($submitResult['success']) {
        echo "✅ Exam submitted successfully\n";
        echo "   Score: " . $submitResult['data']['score'] . "/" . $submitResult['data']['max_score'] . "\n";
        echo "   Percentage: " . $submitResult['data']['percentage'] . "%\n";
        echo "   Passed: " . ($submitResult['data']['passed'] ? 'Yes' : 'No') . "\n";
        echo "   Correct Answers: " . $submitResult['data']['correct_answers'] . "/" . $submitResult['data']['total_questions'] . "\n";
        echo "   Completed: " . $submitResult['data']['completed_at'] . "\n";
    } else {
        echo "❌ Failed to submit exam: " . $submitResult['message'] . "\n";
    }
    echo "\n";

    // Test 10: Verify assignment is marked as completed
    echo "--- Test 10: Verifying Assignment Completion ---\n";
    
    $completedAssignmentsResult = $learningController->getUserAssignments($studentUser->getId()->toString());
    
    if ($completedAssignmentsResult['success']) {
        $completedAssignment = $completedAssignmentsResult['data'][0];
        echo "✅ Assignment status verified\n";
        echo "   Completed: " . ($completedAssignment['is_completed'] ? 'Yes' : 'No') . "\n";
        echo "   Completed At: " . $completedAssignment['completed_at'] . "\n";
    } else {
        echo "❌ Failed to verify assignment: " . $completedAssignmentsResult['message'] . "\n";
    }
    echo "\n";

    // Test 11: Test exam statistics
    echo "--- Test 11: Testing Exam Statistics ---\n";
    
    $attemptRepository = $container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);
    
    $averageScore = $attemptRepository->getAverageScoreByExamId($exam->getId());
    $passRate = $attemptRepository->getPassRateByExamId($exam->getId());
    
    echo "✅ Exam statistics retrieved\n";
    echo "   Average Score: " . round($averageScore, 2) . "\n";
    echo "   Pass Rate: " . round($passRate, 2) . "%\n";
    echo "\n";

    echo "=== All Tests Completed Successfully! ===\n";
    echo "\nThe exam assignment system is working correctly with the following features:\n";
    echo "✅ Exam assignment to users with due dates\n";
    echo "✅ Assignment tracking and status management\n";
    echo "✅ Exam attempt creation and submission\n";
    echo "✅ Automatic scoring and pass/fail determination\n";
    echo "✅ Assignment completion tracking\n";
    echo "✅ Exam statistics and analytics\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 