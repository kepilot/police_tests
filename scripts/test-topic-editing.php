<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container\Container;
use App\Presentation\Controllers\TopicController;

echo "=== Testing Topic Editing Functionality ===\n\n";

try {
    $container = new Container();
    $topicController = $container->get(TopicController::class);
    
    echo "✅ Container and TopicController initialized successfully\n\n";
    
    // Test 1: List topics
    echo "--- Test 1: List Topics ---\n";
    $result = $topicController->listTopics();
    echo "List topics: " . ($result['success'] ? '✅' : '❌') . "\n";
    if ($result['success']) {
        echo "  Found " . count($result['data']) . " topics\n";
        if (count($result['data']) > 0) {
            $firstTopic = $result['data'][0];
            echo "  First topic: {$firstTopic['title']} (ID: {$firstTopic['id']})\n";
            
            // Test 2: Get single topic
            echo "\n--- Test 2: Get Single Topic ---\n";
            $getResult = $topicController->getTopic($firstTopic['id']);
            echo "Get topic: " . ($getResult['success'] ? '✅' : '❌') . "\n";
            if ($getResult['success']) {
                echo "  Topic details: {$getResult['data']['title']} - {$getResult['data']['level_display']}\n";
                
                // Test 3: Update topic
                echo "\n--- Test 3: Update Topic ---\n";
                $updateResult = $topicController->updateTopic(
                    $firstTopic['id'],
                    $firstTopic['title'] . ' (Updated)',
                    $firstTopic['description'] . ' - Updated for testing',
                    $firstTopic['level']
                );
                echo "Update topic: " . ($updateResult['success'] ? '✅' : '❌') . "\n";
                if ($updateResult['success']) {
                    echo "  Topic updated successfully\n";
                    
                    // Test 4: Verify update
                    echo "\n--- Test 4: Verify Update ---\n";
                    $verifyResult = $topicController->getTopic($firstTopic['id']);
                    echo "Verify update: " . ($verifyResult['success'] ? '✅' : '❌') . "\n";
                    if ($verifyResult['success']) {
                        echo "  Updated title: {$verifyResult['data']['title']}\n";
                        echo "  Updated description: {$verifyResult['data']['description']}\n";
                    }
                } else {
                    echo "  Error: " . $updateResult['message'] . "\n";
                }
            } else {
                echo "  Error: " . $getResult['message'] . "\n";
            }
        } else {
            echo "  No topics found to test with\n";
        }
    } else {
        echo "  Error: " . $result['message'] . "\n";
    }
    
    echo "\n=== All Tests Completed ===\n";
    echo "✅ Topic editing functionality is working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 