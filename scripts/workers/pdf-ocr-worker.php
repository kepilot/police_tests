<?php

/**
 * PDF OCR Worker
 * 
 * Este worker procesa trabajos de PDF OCR desde la cola de RabbitMQ.
 * Convierte PDFs a imágenes y encola trabajos de Gemini API.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Infrastructure\Services\GeminiOcrService;
use App\Infrastructure\Services\QueueService;
use PhpAmqpLib\Message\AMQPMessage;

echo "=== PDF OCR Worker Started ===\n";
echo "Waiting for PDF OCR jobs...\n\n";

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
            echo "Processing PDF OCR job...\n";
            $ocrService->processPdfOcrJob($message);
            echo "PDF OCR job processed successfully\n\n";
        } catch (Exception $e) {
            echo "Error processing PDF OCR job: " . $e->getMessage() . "\n\n";
            // El servicio ya maneja el acknowledgment/reject
        }
    };
    
    // Consumir mensajes de la cola
    echo "Starting to consume from PDF OCR queue...\n";
    $queueService->consume(QueueService::QUEUE_PDF_OCR, $callback);
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
} 