<?php

namespace App\Infrastructure\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class QueueService
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    
    // Queue names
    public const QUEUE_PDF_OCR = 'pdf_ocr_queue';
    public const QUEUE_GEMINI_API = 'gemini_api_queue';
    public const QUEUE_RESULTS = 'results_queue';
    public const QUEUE_DEAD_LETTER = 'dead_letter_queue';
    
    // Exchange names
    public const EXCHANGE_PDF_OCR = 'pdf_ocr_exchange';
    public const EXCHANGE_GEMINI_API = 'gemini_api_exchange';
    public const EXCHANGE_RESULTS = 'results_exchange';

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq',
            $_ENV['RABBITMQ_PORT'] ?? 5672,
            $_ENV['RABBITMQ_USER'] ?? 'admin',
            $_ENV['RABBITMQ_PASS'] ?? 'admin123'
        );
        
        $this->channel = $this->connection->channel();
        $this->setupQueues();
    }

    /**
     * Setup all queues and exchanges
     */
    private function setupQueues(): void
    {
        // Declare exchanges
        $this->channel->exchange_declare(self::EXCHANGE_PDF_OCR, 'direct', false, true, false);
        $this->channel->exchange_declare(self::EXCHANGE_GEMINI_API, 'direct', false, true, false);
        $this->channel->exchange_declare(self::EXCHANGE_RESULTS, 'direct', false, true, false);

        // Declare dead letter exchange
        $this->channel->exchange_declare('dead_letter_exchange', 'direct', false, true, false);
        $this->channel->queue_declare(self::QUEUE_DEAD_LETTER, false, true, false, false);

        // Declare main queues with dead letter configuration
        $this->channel->queue_declare(
            self::QUEUE_PDF_OCR,
            false,
            true,
            false,
            false,
            false,
            [
                'x-dead-letter-exchange' => 'dead_letter_exchange',
                'x-dead-letter-routing-key' => 'dead_letter'
            ]
        );

        $this->channel->queue_declare(
            self::QUEUE_GEMINI_API,
            false,
            true,
            false,
            false,
            false,
            [
                'x-dead-letter-exchange' => 'dead_letter_exchange',
                'x-dead-letter-routing-key' => 'dead_letter'
            ]
        );

        $this->channel->queue_declare(
            self::QUEUE_RESULTS,
            false,
            true,
            false,
            false,
            false,
            [
                'x-dead-letter-exchange' => 'dead_letter_exchange',
                'x-dead-letter-routing-key' => 'dead_letter'
            ]
        );

        // Bind queues to exchanges
        $this->channel->queue_bind(self::QUEUE_PDF_OCR, self::EXCHANGE_PDF_OCR, 'pdf_ocr');
        $this->channel->queue_bind(self::QUEUE_GEMINI_API, self::EXCHANGE_GEMINI_API, 'gemini_api');
        $this->channel->queue_bind(self::QUEUE_RESULTS, self::EXCHANGE_RESULTS, 'results');
        $this->channel->queue_bind(self::QUEUE_DEAD_LETTER, 'dead_letter_exchange', 'dead_letter');
    }

    /**
     * Publish a PDF OCR job to the queue
     */
    public function publishPdfOcrJob(array $jobData): void
    {
        $message = new AMQPMessage(
            json_encode($jobData),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
                'timestamp' => time()
            ]
        );

        $this->channel->basic_publish($message, self::EXCHANGE_PDF_OCR, 'pdf_ocr');
        error_log("PDF OCR job published to queue: " . json_encode($jobData));
    }

    /**
     * Publish a Gemini API job to the queue
     */
    public function publishGeminiApiJob(array $jobData): void
    {
        $message = new AMQPMessage(
            json_encode($jobData),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
                'timestamp' => time()
            ]
        );

        $this->channel->basic_publish($message, self::EXCHANGE_GEMINI_API, 'gemini_api');
        error_log("Gemini API job published to queue: " . json_encode($jobData));
    }

    /**
     * Publish a results job to the queue
     */
    public function publishResultsJob(array $jobData): void
    {
        $message = new AMQPMessage(
            json_encode($jobData),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
                'timestamp' => time()
            ]
        );

        $this->channel->basic_publish($message, self::EXCHANGE_RESULTS, 'results');
        error_log("Results job published to queue: " . json_encode($jobData));
    }

    /**
     * Consume messages from a queue
     */
    public function consume(string $queueName, callable $callback): void
    {
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queueName, '', false, false, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    /**
     * Acknowledge a message
     */
    public function acknowledge(AMQPMessage $message): void
    {
        $message->ack();
    }

    /**
     * Reject a message and requeue it
     */
    public function reject(AMQPMessage $message, bool $requeue = true): void
    {
        $message->reject($requeue);
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats(string $queueName): array
    {
        $queueInfo = $this->channel->queue_declare($queueName, false, true, false, false);
        return [
            'queue_name' => $queueName,
            'message_count' => $queueInfo[1],
            'consumer_count' => $queueInfo[2]
        ];
    }

    /**
     * Close the connection
     */
    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Get the channel for advanced operations
     */
    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }
} 