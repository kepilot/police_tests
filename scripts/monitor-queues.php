<?php

/**
 * Queue Monitor Script
 * 
 * Este script monitorea el estado de las colas de RabbitMQ
 * y muestra estadísticas de procesamiento.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Services\QueueService;

echo "=== RabbitMQ Queue Monitor ===\n\n";

try {
    $queueService = new QueueService();
    
    // Obtener estadísticas de todas las colas
    $queues = [
        QueueService::QUEUE_PDF_OCR => 'PDF OCR Queue',
        QueueService::QUEUE_GEMINI_API => 'Gemini API Queue',
        QueueService::QUEUE_RESULTS => 'Results Queue',
        QueueService::QUEUE_DEAD_LETTER => 'Dead Letter Queue'
    ];
    
    echo "Queue Statistics:\n";
    echo str_repeat('-', 60) . "\n";
    
    foreach ($queues as $queueName => $queueDescription) {
        $stats = $queueService->getQueueStats($queueName);
        
        echo sprintf(
            "%-20s | Messages: %-5s | Consumers: %-5s\n",
            $queueDescription,
            $stats['message_count'],
            $stats['consumer_count']
        );
    }
    
    echo str_repeat('-', 60) . "\n\n";
    
    // Mostrar información adicional
    echo "Queue Information:\n";
    echo "- PDF OCR Queue: Convierte PDFs a imágenes\n";
    echo "- Gemini API Queue: Procesa imágenes con Gemini Vision API\n";
    echo "- Results Queue: Guarda resultados y envía notificaciones\n";
    echo "- Dead Letter Queue: Mensajes que fallaron después de múltiples intentos\n\n";
    
    echo "To view detailed RabbitMQ management interface:\n";
    echo "  http://localhost:15672\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n\n";
    
    $queueService->close();
    
} catch (Exception $e) {
    echo "❌ Error monitoring queues: " . $e->getMessage() . "\n";
    exit(1);
} 