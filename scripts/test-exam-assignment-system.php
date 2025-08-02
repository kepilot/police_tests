<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Infrastructure\Persistence\DatabaseConnection;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ExamId;
use App\Application\Commands\AssignExamCommand;
use App\Application\Commands\AssignExamHandler;

echo "🧪 Testing Exam Assignment System\n";
echo "================================\n\n";

try {
    // Initialize container and database connection
    $container = new Container();
    $connection = new DatabaseConnection();
    $pdo = $connection->getConnection();
    
    echo "✅ Database connection established\n";
    
    // Get the exam assignment handler
    $handler = $container->get(AssignExamHandler::class);
    echo "✅ Exam assignment handler loaded\n";
    
    // Get some test user IDs and exam IDs from the database
    $stmt = $pdo->query("SELECT id FROM users LIMIT 2");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($users)) {
        echo "❌ No users found in database. Please create some users first.\n";
        exit(1);
    }
    
    $stmt = $pdo->query("SELECT id FROM exams LIMIT 1");
    $exams = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($exams)) {
        echo "❌ No exams found in database. Please create some exams first.\n";
        exit(1);
    }
    
    $userId = $users[0];
    $examId = $exams[0];
    $assignedBy = $users[1] ?? $users[0]; // Use second user as admin, or first if only one exists
    
    echo "📋 Test Data:\n";
    echo "   User ID: $userId\n";
    echo "   Exam ID: $examId\n";
    echo "   Assigned By: $assignedBy\n\n";
    
    // Test 1: Assign exam without due date
    echo "🔹 Test 1: Assigning exam without due date...\n";
    $command1 = new AssignExamCommand(
        UserId::fromString($userId),
        ExamId::fromString($examId),
        UserId::fromString($assignedBy)
    );
    
    $assignment1 = $handler($command1);
    echo "✅ Exam assigned successfully! Assignment ID: " . $assignment1->getId()->toString() . "\n";
    
    // Test 2: Assign exam with due date
    echo "\n🔹 Test 2: Assigning exam with due date...\n";
    $dueDate = new DateTimeImmutable('+7 days');
    $command2 = new AssignExamCommand(
        UserId::fromString($userId),
        ExamId::fromString($examId),
        UserId::fromString($assignedBy),
        $dueDate
    );
    
    try {
        $assignment2 = $handler($command2);
        echo "✅ Exam assigned with due date successfully! Assignment ID: " . $assignment2->getId()->toString() . "\n";
    } catch (Exception $e) {
        echo "⚠️  Expected error (duplicate assignment): " . $e->getMessage() . "\n";
    }
    
    // Test 3: Check assignment status
    echo "\n🔹 Test 3: Checking assignment status...\n";
    $repository = $container->get(\App\Domain\Repositories\ExamAssignmentRepositoryInterface::class);
    $userAssignments = $repository->findByUserId(UserId::fromString($userId));
    
    echo "📊 User has " . count($userAssignments) . " assignment(s):\n";
    foreach ($userAssignments as $assignment) {
        echo "   - Assignment ID: " . $assignment->getId()->toString() . "\n";
        echo "     Exam ID: " . $assignment->getExamId()->toString() . "\n";
        echo "     Assigned: " . $assignment->getAssignedAt()->format('Y-m-d H:i:s') . "\n";
        echo "     Due Date: " . ($assignment->getDueDate() ? $assignment->getDueDate()->format('Y-m-d H:i:s') : 'None') . "\n";
        echo "     Completed: " . ($assignment->isCompleted() ? 'Yes' : 'No') . "\n";
        echo "     Overdue: " . ($assignment->isOverdue() ? 'Yes' : 'No') . "\n";
    }
    
    // Test 4: Mark assignment as completed
    echo "\n🔹 Test 4: Marking assignment as completed...\n";
    if (!empty($userAssignments)) {
        $firstAssignment = $userAssignments[0];
        $firstAssignment->markAsCompleted();
        $repository->save($firstAssignment);
        echo "✅ Assignment marked as completed!\n";
        
        // Verify the change
        $updatedAssignment = $repository->findById($firstAssignment->getId());
        echo "   Verification - Completed: " . ($updatedAssignment->isCompleted() ? 'Yes' : 'No') . "\n";
        echo "   Completed At: " . ($updatedAssignment->getCompletedAt() ? $updatedAssignment->getCompletedAt()->format('Y-m-d H:i:s') : 'None') . "\n";
    }
    
    // Test 5: Check statistics
    echo "\n🔹 Test 5: Checking assignment statistics...\n";
    $totalAssignments = $repository->countByUserId(UserId::fromString($userId));
    $completedAssignments = $repository->countCompletedByUserId(UserId::fromString($userId));
    $overdueAssignments = $repository->countOverdueByUserId(UserId::fromString($userId));
    
    echo "📈 Statistics for user $userId:\n";
    echo "   Total assignments: $totalAssignments\n";
    echo "   Completed: $completedAssignments\n";
    echo "   Overdue: $overdueAssignments\n";
    echo "   Pending: " . ($totalAssignments - $completedAssignments) . "\n";
    
    echo "\n🎉 All tests completed successfully!\n";
    echo "\n💡 Next steps:\n";
    echo "   1. Access the dashboard at http://localhost/dashboard.html\n";
    echo "   2. Access the admin panel at http://localhost/admin.html\n";
    echo "   3. Try assigning exams to users through the admin interface\n";
    echo "   4. Check the user dashboard to see assigned exams\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 