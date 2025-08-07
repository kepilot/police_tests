<?php

/**
 * Gemini API Worker
 * 
 * Este worker procesa trabajos de Gemini API desde la cola de RabbitMQ.
 * Extrae texto de imágenes usando Gemini Vision API.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Services\GeminiOcrService;
use App\Infrastructure\Services\QueueService;
use PhpAmqpLib\Message\AMQPMessage;

echo "=== Gemini API Worker Started ===\n";
echo "Waiting for Gemini API jobs...\n\n";

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
            echo "Processing Gemini API job...\n";
            $ocrService->processGeminiApiJob($message);
            echo "Gemini API job processed successfully\n\n";
        } catch (Exception $e) {
            echo "Error processing Gemini API job: " . $e->getMessage() . "\n\n";
            // El servicio ya maneja el acknowledgment/reject
        }
    };
    
    // Consumir mensajes de la cola
    echo "Starting to consume from Gemini API queue...\n";
    $queueService->consume(QueueService::QUEUE_GEMINI_API, $callback);
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
} 