<?php

/**
 * Test Queue System Script
 * 
 * Este script prueba el sistema de colas de RabbitMQ
 * enviando trabajos de prueba y verificando el procesamiento.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Services\QueueService;
use App\Infrastructure\Services\GeminiOcrService;

echo "=== RabbitMQ Queue System Test ===\n\n";

try {
    // Verificar configuración
    $apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    if (empty($apiKey)) {
        echo "❌ GEMINI_API_KEY not configured\n";
        echo "Please set your Gemini API key in env.local\n\n";
        exit(1);
    }

    echo "✅ Gemini API key configured\n";

    // Inicializar servicios
    $queueService = new QueueService();
    $ocrService = new GeminiOcrService($apiKey);

    echo "✅ Queue service initialized\n\n";

    // Test 1: Verificar conexión a RabbitMQ
    echo "Test 1: RabbitMQ Connection\n";
    echo "---------------------------\n";
    
    $stats = $queueService->getQueueStats(QueueService::QUEUE_PDF_OCR);
    echo "PDF OCR Queue: " . $stats['message_count'] . " messages, " . $stats['consumer_count'] . " consumers\n";
    
    $stats = $queueService->getQueueStats(QueueService::QUEUE_GEMINI_API);
    echo "Gemini API Queue: " . $stats['message_count'] . " messages, " . $stats['consumer_count'] . " consumers\n";
    
    $stats = $queueService->getQueueStats(QueueService::QUEUE_RESULTS);
    echo "Results Queue: " . $stats['message_count'] . " messages, " . $stats['consumer_count'] . " consumers\n";
    
    echo "✅ RabbitMQ connection successful\n\n";

    // Test 2: Publicar trabajo de prueba
    echo "Test 2: Publishing Test Job\n";
    echo "----------------------------\n";
    
    $testJob = [
        'job_id' => 'test_' . uniqid(),
        'user_id' => 'test_user',
        'pdf_path' => '/tmp/test.pdf',
        'status' => 'test',
        'created_at' => time(),
        'test_mode' => true
    ];
    
    $queueService->publishPdfOcrJob($testJob);
    echo "✅ Test job published to PDF OCR queue\n";
    
    // Verificar que el mensaje esté en la cola
    $stats = $queueService->getQueueStats(QueueService::QUEUE_PDF_OCR);
    echo "PDF OCR Queue now has: " . $stats['message_count'] . " messages\n\n";

    // Test 3: Publicar trabajo de Gemini API
    echo "Test 3: Publishing Gemini API Test Job\n";
    echo "--------------------------------------\n";
    
    $geminiJob = [
        'job_id' => 'test_' . uniqid(),
        'user_id' => 'test_user',
        'page_number' => 1,
        'image_path' => '/tmp/test_image.png',
        'status' => 'test',
        'created_at' => time(),
        'test_mode' => true
    ];
    
    $queueService->publishGeminiApiJob($geminiJob);
    echo "✅ Test job published to Gemini API queue\n";
    
    // Verificar que el mensaje esté en la cola
    $stats = $queueService->getQueueStats(QueueService::QUEUE_GEMINI_API);
    echo "Gemini API Queue now has: " . $stats['message_count'] . " messages\n\n";

    // Test 4: Publicar trabajo de resultados
    echo "Test 4: Publishing Results Test Job\n";
    echo "-----------------------------------\n";
    
    $resultsJob = [
        'job_id' => 'test_' . uniqid(),
        'user_id' => 'test_user',
        'page_number' => 1,
        'questions' => [
            [
                'question' => 'Test question?',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_option' => 0,
                'type' => 'multiple_choice',
                'points' => 1
            ]
        ],
        'status' => 'test',
        'created_at' => time(),
        'test_mode' => true
    ];
    
    $queueService->publishResultsJob($resultsJob);
    echo "✅ Test job published to Results queue\n";
    
    // Verificar que el mensaje esté en la cola
    $stats = $queueService->getQueueStats(QueueService::QUEUE_RESULTS);
    echo "Results Queue now has: " . $stats['message_count'] . " messages\n\n";

    // Test 5: Verificar estado final de todas las colas
    echo "Test 5: Final Queue Status\n";
    echo "--------------------------\n";
    
    $queues = [
        QueueService::QUEUE_PDF_OCR => 'PDF OCR Queue',
        QueueService::QUEUE_GEMINI_API => 'Gemini API Queue',
        QueueService::QUEUE_RESULTS => 'Results Queue',
        QueueService::QUEUE_DEAD_LETTER => 'Dead Letter Queue'
    ];
    
    foreach ($queues as $queueName => $queueDescription) {
        $stats = $queueService->getQueueStats($queueName);
        echo sprintf(
            "%-20s | Messages: %-5s | Consumers: %-5s\n",
            $queueDescription,
            $stats['message_count'],
            $stats['consumer_count']
        );
    }
    
    echo "\n✅ All tests completed successfully!\n\n";
    
    echo "Next steps:\n";
    echo "1. Start workers to process the test jobs:\n";
    echo "   docker-compose exec app php scripts/workers/pdf-ocr-worker.php\n";
    echo "   docker-compose exec app php scripts/workers/gemini-api-worker.php\n";
    echo "   docker-compose exec app php scripts/workers/results-worker.php\n\n";
    
    echo "2. Monitor queue processing:\n";
    echo "   docker-compose exec app php scripts/monitor-queues.php\n\n";
    
    echo "3. View RabbitMQ management interface:\n";
    echo "   http://localhost:15672 (admin/admin123)\n\n";
    
    $queueService->close();
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} 