<?php

/**
 * Start Workers Script
 * 
 * Este script inicia todos los workers de RabbitMQ para procesar
 * trabajos de OCR de PDFs escaneados.
 */

echo "=== Starting RabbitMQ Workers ===\n\n";

// Verificar que RabbitMQ esté disponible
$rabbitmqHost = $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq';
$rabbitmqPort = $_ENV['RABBITMQ_PORT'] ?? 5672;

echo "Checking RabbitMQ connection...\n";
$connection = @fsockopen($rabbitmqHost, $rabbitmqPort, $errno, $errstr, 5);

if (!$connection) {
    echo "❌ Cannot connect to RabbitMQ at {$rabbitmqHost}:{$rabbitmqPort}\n";
    echo "Error: {$errstr} ({$errno})\n\n";
    echo "Make sure RabbitMQ is running:\n";
    echo "  docker-compose up -d rabbitmq\n\n";
    exit(1);
}

fclose($connection);
echo "✅ RabbitMQ is accessible\n\n";

// Verificar que la API key de Gemini esté configurada
$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
if (empty($apiKey)) {
    echo "❌ GEMINI_API_KEY environment variable is not set\n\n";
    echo "Please set your Gemini API key in env.local:\n";
    echo "  GEMINI_API_KEY=your_api_key_here\n\n";
    exit(1);
}

echo "✅ Gemini API key is configured\n\n";

// Workers disponibles
$workers = [
    'pdf-ocr-worker.php' => 'PDF OCR Worker',
    'gemini-api-worker.php' => 'Gemini API Worker',
    'results-worker.php' => 'Results Worker'
];

echo "Available workers:\n";
foreach ($workers as $file => $name) {
    echo "  - {$name} ({$file})\n";
}

echo "\nTo start a specific worker:\n";
echo "  docker-compose exec app php scripts/workers/{worker_file}\n\n";

echo "To start all workers in background:\n";
echo "  docker-compose exec -d app php scripts/workers/pdf-ocr-worker.php &\n";
echo "  docker-compose exec -d app php scripts/workers/gemini-api-worker.php &\n";
echo "  docker-compose exec -d app php scripts/workers/results-worker.php &\n\n";

echo "To monitor workers:\n";
echo "  docker-compose exec app php scripts/monitor-queues.php\n\n";

echo "=== Workers Ready ===\n";
echo "You can now upload scanned PDFs through the admin panel.\n";
echo "The system will automatically queue and process them.\n"; 