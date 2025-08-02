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

echo "🎓 EXAM ASSIGNMENT SYSTEM DEMONSTRATION\n";
echo "=====================================\n\n";

try {
    $container = new Container();
    $learningController = new \App\Presentation\Controllers\LearningController($container);
    
    echo "✅ System initialized successfully\n\n";

    // Step 1: Create or get users
    echo "👥 STEP 1: Setting up users...\n";
    $userRepository = $container->get(\App\Domain\Repositories\UserRepositoryInterface::class);
    $registerHandler = $container->get(\App\Application\Commands\RegisterUserHandler::class);
    
    // Get or create admin
    $adminUser = $userRepository->findByEmail('demo.admin@example.com');
    if (!$adminUser) {
        $adminCommand = new \App\Application\Commands\RegisterUserCommand(
            'Demo Admin',
            new Email('demo.admin@example.com'),
            new Password('AdminPass123!')
        );
        $adminUser = $registerHandler($adminCommand);
        echo "   ✅ Created admin: " . $adminUser->getName() . "\n";
    } else {
        echo "   ✅ Found admin: " . $adminUser->getName() . "\n";
    }
    
    // Get or create student
    $studentUser = $userRepository->findByEmail('demo.student@example.com');
    if (!$studentUser) {
        $studentCommand = new \App\Application\Commands\RegisterUserCommand(
            'Demo Student',
            new Email('demo.student@example.com'),
            new Password('StudentPass123!')
        );
        $studentUser = $registerHandler($studentCommand);
        echo "   ✅ Created student: " . $studentUser->getName() . "\n";
    } else {
        echo "   ✅ Found student: " . $studentUser->getName() . "\n";
    }
    echo "\n";

    // Step 2: Create a comprehensive exam
    echo "📝 STEP 2: Creating a comprehensive exam...\n";
    
    // Create topic
    $topicRepository = $container->get(\App\Domain\Repositories\TopicRepositoryInterface::class);
    $topic = new Topic(
        new TopicTitle("Web Development Fundamentals"),
        new TopicDescription("Core concepts of web development including HTML, CSS, and JavaScript"),
        new TopicLevel("intermediate")
    );
    $topicRepository->save($topic);
    echo "   ✅ Created topic: " . $topic->getTitle()->value() . "\n";
    
    // Create exam
    $examRepository = $container->get(\App\Domain\Repositories\ExamRepositoryInterface::class);
    $exam = new Exam(
        new ExamTitle("Web Development Assessment"),
        new ExamDescription("Test your knowledge of web development fundamentals"),
        new ExamDuration(45),
        new ExamPassingScore(75),
        $topic->getId()
    );
    $examRepository->save($exam);
    echo "   ✅ Created exam: " . $exam->getTitle()->value() . " (45 minutes, 75% passing score)\n";
    
    // Create questions
    $questionRepository = $container->get(\App\Domain\Repositories\QuestionRepositoryInterface::class);
    $questions = [
        [
            'text' => 'What does HTML stand for?',
            'type' => 'multiple_choice',
            'options' => ['HyperText Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink and Text Markup Language'],
            'correct' => 0,
            'points' => 5
        ],
        [
            'text' => 'Which CSS property is used to change the text color?',
            'type' => 'multiple_choice',
            'options' => ['text-color', 'color', 'font-color', 'text-style'],
            'correct' => 1,
            'points' => 5
        ],
        [
            'text' => 'JavaScript is a programming language.',
            'type' => 'true_false',
            'options' => ['True', 'False'],
            'correct' => 0,
            'points' => 3
        ],
        [
            'text' => 'What is the correct way to write a JavaScript array?',
            'type' => 'multiple_choice',
            'options' => ['var colors = "red", "green", "blue"', 'var colors = (1:"red", 2:"green", 3:"blue")', 'var colors = ["red", "green", "blue"]', 'var colors = "red" + "green" + "blue"'],
            'correct' => 2,
            'points' => 5
        ],
        [
            'text' => 'CSS stands for Cascading Style Sheets.',
            'type' => 'true_false',
            'options' => ['True', 'False'],
            'correct' => 0,
            'points' => 3
        ],
        [
            'text' => 'Which HTML tag is used to define an internal style sheet?',
            'type' => 'multiple_choice',
            'options' => ['<script>', '<style>', '<css>', '<link>'],
            'correct' => 1,
            'points' => 4
        ],
        [
            'text' => 'JavaScript can be used to create interactive web pages.',
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
    }
    echo "   ✅ Created " . count($questions) . " questions\n\n";

    // Step 3: Assign exam to student
    echo "📋 STEP 3: Assigning exam to student...\n";
    $assignmentResult = $learningController->assignExamToUser(
        $studentUser->getId()->toString(),
        $exam->getId()->toString(),
        $adminUser->getId()->toString(),
        (new DateTime())->modify('+3 days')->format('Y-m-d H:i:s') // Due in 3 days
    );
    
    if ($assignmentResult['success']) {
        echo "   ✅ Exam assigned successfully\n";
        echo "   📅 Due date: " . $assignmentResult['data']['due_date'] . "\n";
    } else {
        echo "   ❌ Failed to assign exam: " . $assignmentResult['message'] . "\n";
        exit(1);
    }
    echo "\n";

    // Step 4: Show student's assignments
    echo "📊 STEP 4: Student's current assignments...\n";
    $assignmentsResult = $learningController->getUserAssignments($studentUser->getId()->toString());
    
    if ($assignmentsResult['success']) {
        foreach ($assignmentsResult['data'] as $assignment) {
            echo "   📝 Assignment ID: " . substr($assignment['id'], 0, 8) . "...\n";
            echo "      Status: " . ($assignment['is_completed'] ? '✅ Completed' : '⏳ Pending') . "\n";
            echo "      Due: " . $assignment['due_date'] . "\n";
            echo "      Overdue: " . ($assignment['is_overdue'] ? '⚠️  Yes' : '✅ No') . "\n";
        }
    }
    echo "\n";

    // Step 5: Start exam attempt
    echo "🚀 STEP 5: Starting exam attempt...\n";
    $startResult = $learningController->startExamAttempt(
        $studentUser->getId()->toString(),
        $exam->getId()->toString()
    );
    
    if ($startResult['success']) {
        echo "   ✅ Exam attempt started\n";
        echo "   🕐 Duration: " . $startResult['data']['exam']['duration_minutes'] . " minutes\n";
        echo "   📝 Questions: " . count($startResult['data']['questions']) . "\n";
        echo "   🎯 Passing score: " . $startResult['data']['exam']['passing_score_percentage'] . "%\n";
        
        $attemptId = $startResult['data']['attempt_id'];
        $examQuestions = $startResult['data']['questions'];
    } else {
        echo "   ❌ Failed to start exam: " . $startResult['message'] . "\n";
        exit(1);
    }
    echo "\n";

    // Step 6: Simulate exam completion with mixed results
    echo "✍️  STEP 6: Simulating exam completion...\n";
    
    // Simulate answers (some correct, some incorrect)
    $answers = [];
    foreach ($examQuestions as $index => $question) {
        // Mix correct and incorrect answers for realistic results
        if ($index % 2 == 0) {
            $answers[$question['id']] = $question['type'] === 'true_false' ? 0 : 0; // Correct for our questions
        } else {
            $answers[$question['id']] = $question['type'] === 'true_false' ? 1 : 1; // Incorrect
        }
    }
    
    $submitResult = $learningController->submitExamAttempt($attemptId, $answers);
    
    if ($submitResult['success']) {
        echo "   ✅ Exam submitted successfully\n";
        echo "   📊 Score: " . $submitResult['data']['score'] . "/" . $submitResult['data']['max_score'] . "\n";
        echo "   📈 Percentage: " . $submitResult['data']['percentage'] . "%\n";
        echo "   🎯 Result: " . ($submitResult['data']['passed'] ? '✅ PASSED' : '❌ FAILED') . "\n";
        echo "   ✅ Correct answers: " . $submitResult['data']['correct_answers'] . "/" . $submitResult['data']['total_questions'] . "\n";
    } else {
        echo "   ❌ Failed to submit exam: " . $submitResult['message'] . "\n";
    }
    echo "\n";

    // Step 7: Show updated assignment status
    echo "📋 STEP 7: Updated assignment status...\n";
    $updatedAssignmentsResult = $learningController->getUserAssignments($studentUser->getId()->toString());
    
    if ($updatedAssignmentsResult['success']) {
        foreach ($updatedAssignmentsResult['data'] as $assignment) {
            if ($assignment['exam_id'] === $exam->getId()->toString()) {
                echo "   📝 Assignment: " . substr($assignment['id'], 0, 8) . "...\n";
                echo "      Status: " . ($assignment['is_completed'] ? '✅ Completed' : '⏳ Pending') . "\n";
                echo "      Completed at: " . ($assignment['completed_at'] ?? 'N/A') . "\n";
            }
        }
    }
    echo "\n";

    // Step 8: Show exam statistics
    echo "📊 STEP 8: Exam statistics...\n";
    $attemptRepository = $container->get(\App\Domain\Repositories\ExamAttemptRepositoryInterface::class);
    
    $averageScore = $attemptRepository->getAverageScoreByExamId($exam->getId());
    $passRate = $attemptRepository->getPassRateByExamId($exam->getId());
    $totalAttempts = $attemptRepository->countByExamId($exam->getId());
    
    echo "   📈 Total attempts: " . $totalAttempts . "\n";
    echo "   📊 Average score: " . round($averageScore, 1) . "\n";
    echo "   🎯 Pass rate: " . round($passRate, 1) . "%\n";
    echo "\n";

    // Step 9: Show pending assignments
    echo "📋 STEP 9: Student's pending assignments...\n";
    $pendingResult = $learningController->getPendingAssignments($studentUser->getId()->toString());
    
    if ($pendingResult['success']) {
        echo "   📝 Pending assignments: " . count($pendingResult['data']) . "\n";
        foreach ($pendingResult['data'] as $assignment) {
            echo "      - Assignment ID: " . substr($assignment['id'], 0, 8) . "...\n";
            echo "        Due: " . $assignment['due_date'] . "\n";
            echo "        Overdue: " . ($assignment['is_overdue'] ? '⚠️  Yes' : '✅ No') . "\n";
        }
    }
    echo "\n";

    echo "🎉 DEMONSTRATION COMPLETED SUCCESSFULLY!\n";
    echo "=====================================\n\n";
    
    echo "The exam assignment system includes:\n";
    echo "✅ User management (admin and student roles)\n";
    echo "✅ Topic and exam creation\n";
    echo "✅ Question management (multiple choice, true/false)\n";
    echo "✅ Exam assignment with due dates\n";
    echo "✅ Assignment tracking and status management\n";
    echo "✅ Exam attempt creation and submission\n";
    echo "✅ Automatic scoring and pass/fail determination\n";
    echo "✅ Assignment completion tracking\n";
    echo "✅ Exam statistics and analytics\n";
    echo "✅ Overdue assignment detection\n";
    echo "✅ Pending assignment management\n\n";
    
    echo "Demo credentials:\n";
    echo "👨‍💼 Admin: demo.admin@example.com / AdminPass123!\n";
    echo "👨‍🎓 Student: demo.student@example.com / StudentPass123!\n\n";
    
    echo "You can now:\n";
    echo "1. Access the web interface at http://localhost\n";
    echo "2. Login with the demo credentials\n";
    echo "3. Take the assigned exam\n";
    echo "4. View results and statistics\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 