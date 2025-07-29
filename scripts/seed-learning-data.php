<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateTopicCommand;
use App\Application\Commands\CreateExamCommand;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use App\Domain\ValueObjects\ExamTitle;
use App\Domain\ValueObjects\ExamDescription;
use App\Domain\ValueObjects\ExamDuration;
use App\Domain\ValueObjects\ExamPassingScore;
use App\Domain\ValueObjects\TopicId;

echo "=== Seed Learning Portal Data ===\n\n";

try {
    $container = new Container();
    
    echo "Creating sample topics...\n";
    
    // Sample topics data
    $topicsData = [
        [
            'title' => 'PHP Fundamentals',
            'description' => 'Learn the basics of PHP programming language including syntax, variables, and control structures.',
            'level' => 'beginner'
        ],
        [
            'title' => 'Object-Oriented Programming in PHP',
            'description' => 'Master OOP concepts in PHP including classes, objects, inheritance, and polymorphism.',
            'level' => 'intermediate'
        ],
        [
            'title' => 'PHP Design Patterns',
            'description' => 'Learn common design patterns and their implementation in PHP applications.',
            'level' => 'advanced'
        ],
        [
            'title' => 'PHP Security Best Practices',
            'description' => 'Understand security vulnerabilities and how to protect your PHP applications.',
            'level' => 'advanced'
        ],
        [
            'title' => 'PHP Performance Optimization',
            'description' => 'Learn techniques to optimize PHP application performance and scalability.',
            'level' => 'expert'
        ],
        [
            'title' => 'Database Design with PHP',
            'description' => 'Learn database design principles and how to work with databases in PHP.',
            'level' => 'intermediate'
        ],
        [
            'title' => 'PHP Testing Strategies',
            'description' => 'Master unit testing, integration testing, and test-driven development in PHP.',
            'level' => 'advanced'
        ],
        [
            'title' => 'PHP Framework Development',
            'description' => 'Learn how to build custom PHP frameworks and understand framework architecture.',
            'level' => 'expert'
        ]
    ];
    
    $createdTopics = [];
    $createTopicHandler = $container->get(\App\Application\Commands\CreateTopicHandler::class);
    
    foreach ($topicsData as $topicData) {
        $command = new CreateTopicCommand(
            new TopicTitle($topicData['title']),
            new TopicDescription($topicData['description']),
            new TopicLevel($topicData['level'])
        );
        
        $topic = $createTopicHandler($command);
        $createdTopics[] = $topic;
        
        echo "✅ Created topic: " . $topic->getTitle()->value() . " (" . $topic->getLevel()->getDisplayName() . ")\n";
    }
    
    echo "\nCreating sample exams...\n";
    
    // Sample exams data
    $examsData = [
        [
            'title' => 'PHP Basics Quiz',
            'description' => 'Test your knowledge of PHP fundamentals including syntax, variables, and basic functions.',
            'duration_minutes' => 30,
            'passing_score_percentage' => 70,
            'topic_index' => 0 // PHP Fundamentals
        ],
        [
            'title' => 'OOP Concepts Test',
            'description' => 'Evaluate your understanding of object-oriented programming concepts in PHP.',
            'duration_minutes' => 45,
            'passing_score_percentage' => 75,
            'topic_index' => 1 // OOP in PHP
        ],
        [
            'title' => 'Design Patterns Assessment',
            'description' => 'Test your knowledge of common design patterns and their implementation.',
            'duration_minutes' => 60,
            'passing_score_percentage' => 80,
            'topic_index' => 2 // Design Patterns
        ],
        [
            'title' => 'Security Fundamentals Exam',
            'description' => 'Assess your understanding of PHP security vulnerabilities and protection methods.',
            'duration_minutes' => 45,
            'passing_score_percentage' => 85,
            'topic_index' => 3 // Security
        ],
        [
            'title' => 'Performance Optimization Test',
            'description' => 'Evaluate your knowledge of PHP performance optimization techniques.',
            'duration_minutes' => 90,
            'passing_score_percentage' => 80,
            'topic_index' => 4 // Performance
        ],
        [
            'title' => 'Database Design Quiz',
            'description' => 'Test your understanding of database design principles and PHP database integration.',
            'duration_minutes' => 40,
            'passing_score_percentage' => 75,
            'topic_index' => 5 // Database Design
        ]
    ];
    
    $createExamHandler = $container->get(\App\Application\Commands\CreateExamHandler::class);
    
    foreach ($examsData as $examData) {
        $topic = $createdTopics[$examData['topic_index']];
        
        $command = new CreateExamCommand(
            new ExamTitle($examData['title']),
            new ExamDescription($examData['description']),
            new ExamDuration($examData['duration_minutes']),
            new ExamPassingScore($examData['passing_score_percentage']),
            $topic->getId()
        );
        
        $exam = $createExamHandler($command);
        
        echo "✅ Created exam: " . $exam->getTitle()->value() . "\n";
        echo "   Duration: " . $exam->getDuration()->getDisplayValue() . "\n";
        echo "   Passing Score: " . $exam->getPassingScore()->getDisplayValue() . "\n";
        echo "   Topic: " . $topic->getTitle()->value() . "\n";
    }
    
    echo "\n=== Data Seeding Completed Successfully! ===\n";
    echo "\nCreated:\n";
    echo "  📚 " . count($createdTopics) . " topics\n";
    echo "  📝 " . count($examsData) . " exams\n";
    echo "\nTopics by level:\n";
    
    $levelCounts = [];
    foreach ($createdTopics as $topic) {
        $level = $topic->getLevel()->value();
        $levelCounts[$level] = ($levelCounts[$level] ?? 0) + 1;
    }
    
    foreach ($levelCounts as $level => $count) {
        echo "  • " . ucfirst($level) . ": " . $count . " topics\n";
    }
    
    echo "\nYou can now:\n";
    echo "  1. Log in as an admin user\n";
    echo "  2. View topics at: GET /topics\n";
    echo "  3. View exams at: GET /exams\n";
    echo "  4. View learning stats at: GET /learning/stats\n";
    echo "  5. Create new topics with: POST /topics\n";
    echo "  6. Create new exams with: POST /exams\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 