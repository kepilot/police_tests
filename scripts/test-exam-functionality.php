<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Presentation\Controllers\ExamAttemptController;
use App\Presentation\Controllers\ExamAssignmentController;
use App\Presentation\Controllers\LearningController;

// Load environment variables
$envFile = __DIR__ . '/../env.local';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../env.example';
}
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Set default values for missing environment variables
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ddd_app';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? 'root';
$_ENV['DB_PASS'] = $_ENV['DB_PASS'] ?? '';
$_ENV['JWT_SECRET'] = $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production';

echo "Testing Exam Functionality\n";
echo "==========================\n\n";

try {
    // Initialize container
    $container = new Container();
    
    // Test 1: Check if we can create a topic
    echo "1. Testing Topic Creation...\n";
    $learningController = new LearningController($container);
    $topicName = 'Test Topic for Exam ' . date('Y-m-d H:i:s');
    $topicResult = $learningController->createTopic(
        $topicName,
        'This is a test topic for exam functionality',
        'beginner'
    );
    
    if ($topicResult['success']) {
        $topicId = $topicResult['data']['id'];
        echo "   ✓ Topic created successfully: $topicId\n";
    } else {
        echo "   ✗ Failed to create topic: " . $topicResult['message'] . "\n";
        exit(1);
    }
    
    // Test 2: Check if we can create an exam
    echo "\n2. Testing Exam Creation...\n";
    $examName = 'Test Exam ' . date('Y-m-d H:i:s');
    $examResult = $learningController->createExam(
        $examName,
        'This is a test exam for functionality testing',
        30, // 30 minutes
        70, // 70% passing score
        $topicId
    );
    
    if ($examResult['success']) {
        $examId = $examResult['data']['id'];
        echo "   ✓ Exam created successfully: $examId\n";
    } else {
        echo "   ✗ Failed to create exam: " . $examResult['message'] . "\n";
        exit(1);
    }
    
    // Test 3: Check if we can create a question
    echo "\n3. Testing Question Creation...\n";
    $questionResult = $learningController->createQuestion(
        'What is 2 + 2?',
        'single_choice',
        $examId,
        ['3', '4', '5', '6'],
        1, // Correct answer is index 1 (4)
        1, // 1 point
        [$topicId]
    );
    
    if ($questionResult['success']) {
        $questionId = $questionResult['data']['id'];
        echo "   ✓ Question created successfully: $questionId\n";
    } else {
        echo "   ✗ Failed to create question: " . $questionResult['message'] . "\n";
        exit(1);
    }
    
    // Test 4: Check if we can create a user (if user creation is available)
    echo "\n4. Testing User Creation...\n";
    $userController = new \App\Presentation\Controllers\UserController($container);
    $userEmail = 'test' . time() . '@example.com';
    $userResult = $userController->createUser('Test User', $userEmail);
    
    if ($userResult['success']) {
        $userId = $userResult['data']['id'];
        echo "   ✓ User created successfully: $userId\n";
    } else {
        echo "   ✗ Failed to create user: " . $userResult['message'] . "\n";
        // Try to get an existing user
        $usersResult = $userController->listUsers();
        if ($usersResult['success'] && !empty($usersResult['data'])) {
            $userId = $usersResult['data'][0]['id'];
            echo "   ✓ Using existing user: $userId\n";
        } else {
            echo "   ✗ No users available for testing\n";
            exit(1);
        }
    }
    
    // Test 5: Check if we can assign an exam
    echo "\n5. Testing Exam Assignment...\n";
    $assignmentController = new ExamAssignmentController($container);
    $assignmentResult = $assignmentController->assignExamToUser(
        $userId,
        $examId,
        $userId, // Assign by the same user
        null // No due date
    );
    
    if ($assignmentResult['success']) {
        $assignmentId = $assignmentResult['data']['id'];
        echo "   ✓ Exam assigned successfully: $assignmentId\n";
    } else {
        echo "   ✗ Failed to assign exam: " . $assignmentResult['message'] . "\n";
        exit(1);
    }
    
    // Test 6: Check if we can start an exam attempt
    echo "\n6. Testing Exam Attempt Start...\n";
    $attemptController = new ExamAttemptController($container);
    $attemptResult = $attemptController->startExamAttempt($userId, $examId);
    
    if ($attemptResult['success']) {
        $attemptId = $attemptResult['data']['attempt_id'];
        echo "   ✓ Exam attempt started successfully: $attemptId\n";
        echo "   ✓ Exam has " . count($attemptResult['data']['questions']) . " questions\n";
        echo "   ✓ Time limit: " . $attemptResult['data']['time_limit'] . " seconds\n";
    } else {
        echo "   ✗ Failed to start exam attempt: " . $attemptResult['message'] . "\n";
        exit(1);
    }
    
    // Test 7: Check if we can submit an exam attempt
    echo "\n7. Testing Exam Attempt Submission...\n";
    $answers = [$questionId => 1]; // Answer with index 1 (correct answer)
    $submitResult = $attemptController->submitExamAttempt($attemptId, $answers);
    
    if ($submitResult['success']) {
        echo "   ✓ Exam submitted successfully\n";
        echo "   ✓ Score: " . $submitResult['data']['score']['earned'] . "/" . $submitResult['data']['score']['total'] . "\n";
        echo "   ✓ Percentage: " . $submitResult['data']['score']['percentage'] . "%\n";
        echo "   ✓ Passed: " . ($submitResult['data']['score']['passed'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ✗ Failed to submit exam: " . $submitResult['message'] . "\n";
        exit(1);
    }
    
    echo "\n🎉 All tests passed! Exam functionality is working correctly.\n";
    echo "\nTest Summary:\n";
    echo "- Topic ID: $topicId\n";
    echo "- Exam ID: $examId\n";
    echo "- Question ID: $questionId\n";
    echo "- User ID: $userId\n";
    echo "- Assignment ID: $assignmentId\n";
    echo "- Attempt ID: $attemptId\n";
    
} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 