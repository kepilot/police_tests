<?php

/**
 * Results Worker
 * 
 * Este worker procesa trabajos de resultados desde la cola de RabbitMQ.
 * Guarda los resultados en la base de datos y envía notificaciones.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Services\GeminiOcrService;
use App\Infrastructure\Services\QueueService;
use PhpAmqpLib\Message\AMQPMessage;

echo "=== Results Worker Started ===\n";
echo "Waiting for results jobs...\n\n";

try {
    // Inicializar servicios
    $apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
    if (empty($apiKey)) {
        throw new Exception("GEMINI_API_KEY environment variable is not set");
    }
    
    $ocrService = new GeminiOcrService($apiKey);
    $queueService = new QueueService();
    
    // Callback para procesar mensajes
    $callback = function (AMQPMessage $message) use ($ocrService) {
        try {
            echo "Processing results job...\n";
            $ocrService->processResultsJob($message);
            echo "Results job processed successfully\n\n";
        } catch (Exception $e) {
            echo "Error processing results job: " . $e->getMessage() . "\n\n";
            // El servicio ya maneja el acknowledgment/reject
        }
    };
    
    // Consumir mensajes de la cola
    echo "Starting to consume from results queue...\n";
    $queueService->consume(QueueService::QUEUE_RESULTS, $callback);
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
} 