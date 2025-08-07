<?php

namespace App\Infrastructure\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PhpAmqpLib\Message\AMQPMessage;

class GeminiOcrService
{
    private string $apiKey;
    private Client $httpClient;
    private QueueService $queueService;
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = new Client([
            'timeout' => 60,
            'connect_timeout' => 30
        ]);
        $this->queueService = new QueueService();
    }

    /**
     * Inicia el procesamiento asíncrono de un PDF escaneado usando colas
     */
    public function processPdfAsync(string $pdfPath, string $jobId, string $userId): array
    {
        try {
            error_log("=== ASYNC PDF OCR PROCESSING STARTED ===");
            error_log("Job ID: {$jobId}, User ID: {$userId}");
            
            // Crear el trabajo inicial de PDF OCR
            $pdfJob = [
                'job_id' => $jobId,
                'user_id' => $userId,
                'pdf_path' => $pdfPath,
                'status' => 'queued',
                'created_at' => time(),
                'total_pages' => 0,
                'processed_pages' => 0,
                'questions' => []
            ];
            
            // Publicar el trabajo en la cola de PDF OCR
            $this->queueService->publishPdfOcrJob($pdfJob);
            
            error_log("PDF OCR job queued successfully: {$jobId}");
            
            return [
                'success' => true,
                'job_id' => $jobId,
                'status' => 'queued',
                'message' => 'PDF processing has been queued. You will be notified when complete.'
            ];
            
        } catch (\Exception $e) {
            error_log("Error queuing PDF OCR job: " . $e->getMessage());
            throw new \Exception("Failed to queue PDF processing: " . $e->getMessage());
        }
    }

    /**
     * Procesa un PDF escaneado de forma síncrona (método original para compatibilidad)
     */
    public function extractQuestionsFromPdf(string $pdfPath): array
    {
        try {
            error_log("=== SYNC PDF OCR PROCESSING STARTED ===");
            
            // 1. Convertir cada página del PDF a imagen (recomendado: PNG, 300dpi)
            $imagePaths = $this->convertPdfToImages($pdfPath);
            error_log("Converted PDF to " . count($imagePaths) . " images");
            
            $allQuestions = [];
            foreach ($imagePaths as $pageNumber => $imagePath) {
                error_log("Processing page {$pageNumber} with Gemini OCR");
                
                $text = $this->extractTextFromImageWithGemini($imagePath);
                if (!empty($text)) {
                    $questions = $this->parseQuestionsFromText($text);
                    $allQuestions = array_merge($allQuestions, $questions);
                    error_log("Extracted " . count($questions) . " questions from page {$pageNumber}");
                }
                
                // Limpiar imagen temporal
                @unlink($imagePath);
            }
            
            error_log("=== SYNC PDF OCR PROCESSING COMPLETED ===");
            error_log("Total questions extracted: " . count($allQuestions));
            
            return $allQuestions;
            
        } catch (\Exception $e) {
            error_log("Gemini OCR Error: " . $e->getMessage());
            throw new \Exception("OCR processing failed: " . $e->getMessage());
        }
    }

    /**
     * Worker: Procesa trabajos de PDF OCR desde la cola
     */
    public function processPdfOcrJob(AMQPMessage $message): void
    {
        try {
            $jobData = json_decode($message->body, true);
            $jobId = $jobData['job_id'];
            $pdfPath = $jobData['pdf_path'];
            
            error_log("Processing PDF OCR job: {$jobId}");
            
            // Actualizar estado del trabajo
            $jobData['status'] = 'processing';
            $jobData['started_at'] = time();
            
            // Convertir PDF a imágenes
            $imagePaths = $this->convertPdfToImages($pdfPath);
            $jobData['total_pages'] = count($imagePaths);
            
            error_log("PDF converted to " . count($imagePaths) . " images for job: {$jobId}");
            
            // Procesar cada página y encolar trabajos de Gemini API
            foreach ($imagePaths as $pageNumber => $imagePath) {
                $geminiJob = [
                    'job_id' => $jobId,
                    'user_id' => $jobData['user_id'],
                    'page_number' => $pageNumber,
                    'image_path' => $imagePath,
                    'status' => 'queued',
                    'created_at' => time()
                ];
                
                $this->queueService->publishGeminiApiJob($geminiJob);
                error_log("Gemini API job queued for page {$pageNumber}, job: {$jobId}");
            }
            
            // Acknowledgment del mensaje
            $this->queueService->acknowledge($message);
            
        } catch (\Exception $e) {
            error_log("Error processing PDF OCR job: " . $e->getMessage());
            $this->queueService->reject($message, false); // No requeue, enviar a dead letter
        }
    }

    /**
     * Worker: Procesa trabajos de Gemini API desde la cola
     */
    public function processGeminiApiJob(AMQPMessage $message): void
    {
        try {
            $jobData = json_decode($message->body, true);
            $jobId = $jobData['job_id'];
            $pageNumber = $jobData['page_number'];
            $imagePath = $jobData['image_path'];
            
            error_log("Processing Gemini API job for page {$pageNumber}, job: {$jobId}");
            
            // Actualizar estado del trabajo
            $jobData['status'] = 'processing';
            $jobData['started_at'] = time();
            
            // Extraer texto de la imagen usando Gemini
            $text = $this->extractTextFromImageWithGemini($imagePath);
            $questions = $this->parseQuestionsFromText($text);
            
            // Crear trabajo de resultados
            $resultsJob = [
                'job_id' => $jobId,
                'user_id' => $jobData['user_id'],
                'page_number' => $pageNumber,
                'questions' => $questions,
                'status' => 'completed',
                'completed_at' => time()
            ];
            
            $this->queueService->publishResultsJob($resultsJob);
            
            // Limpiar imagen temporal
            @unlink($imagePath);
            
            error_log("Gemini API job completed for page {$pageNumber}, extracted " . count($questions) . " questions");
            
            // Acknowledgment del mensaje
            $this->queueService->acknowledge($message);
            
        } catch (\Exception $e) {
            error_log("Error processing Gemini API job: " . $e->getMessage());
            $this->queueService->reject($message, true); // Requeue para reintentar
        }
    }

    /**
     * Worker: Procesa trabajos de resultados desde la cola
     */
    public function processResultsJob(AMQPMessage $message): void
    {
        try {
            $jobData = json_decode($message->body, true);
            $jobId = $jobData['job_id'];
            $pageNumber = $jobData['page_number'];
            $questions = $jobData['questions'];
            
            error_log("Processing results job for page {$pageNumber}, job: {$jobId}");
            
            // Aquí podrías guardar los resultados en la base de datos
            // o enviar notificaciones al usuario
            
            // Por ahora, solo loggeamos los resultados
            error_log("Results processed for page {$pageNumber}: " . count($questions) . " questions");
            
            // Acknowledgment del mensaje
            $this->queueService->acknowledge($message);
            
        } catch (\Exception $e) {
            error_log("Error processing results job: " . $e->getMessage());
            $this->queueService->reject($message, false);
        }
    }

    /**
     * Obtiene el estado de un trabajo
     */
    public function getJobStatus(string $jobId): array
    {
        // Aquí implementarías la lógica para obtener el estado del trabajo
        // desde la base de datos o cache
        return [
            'job_id' => $jobId,
            'status' => 'unknown',
            'progress' => 0,
            'total_pages' => 0,
            'processed_pages' => 0
        ];
    }

    /**
     * Convierte páginas del PDF a imágenes usando ImageMagick
     */
    private function convertPdfToImages(string $pdfPath): array
    {
        $outputDir = sys_get_temp_dir() . '/pdf_ocr_' . uniqid();
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Usar ImageMagick para convertir PDF a imágenes
        $command = "convert -density 300 -quality 100 '{$pdfPath}' '{$outputDir}/page_%03d.png'";
        $output = [];
        $returnCode = 0;
        
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Failed to convert PDF to images. ImageMagick required. Error: " . implode("\n", $output));
        }

        // Obtener todas las imágenes generadas
        $files = glob($outputDir . '/page_*.png');
        sort($files); // Asegurar orden correcto

        $imagePaths = [];
        foreach ($files as $index => $file) {
            $imagePaths[$index + 1] = $file;
        }

        return $imagePaths;
    }

    /**
     * Extrae texto de una imagen usando Gemini Vision API
     */
    private function extractTextFromImageWithGemini(string $imagePath): string
    {
        try {
            // Leer la imagen y convertir a base64
            $imageData = file_get_contents($imagePath);
            if ($imageData === false) {
                throw new \Exception("Could not read image file: {$imagePath}");
            }

            $base64Image = base64_encode($imageData);
            $mimeType = $this->getMimeType($imagePath);

            // Preparar el prompt para Gemini
            $prompt = $this->getOcrPrompt();

            // Preparar el payload para la API de Gemini
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topK' => 32,
                    'topP' => 1,
                    'maxOutputTokens' => 4096,
                ]
            ];

            // Hacer la llamada HTTP a Gemini API
            $response = $this->httpClient->post(self::GEMINI_API_URL . '?key=' . $this->apiKey, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);

            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                $extractedText = $responseData['candidates'][0]['content']['parts'][0]['text'];
                error_log("Gemini API response received successfully");
                return $this->cleanExtractedText($extractedText);
            } else {
                error_log("Unexpected Gemini API response format: " . json_encode($responseData));
                throw new \Exception("Unexpected response format from Gemini API");
            }

        } catch (RequestException $e) {
            error_log("Gemini API request failed: " . $e->getMessage());
            if ($e->hasResponse()) {
                $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
                error_log("Gemini API error details: " . json_encode($errorResponse));
            }
            throw new \Exception("Gemini API request failed: " . $e->getMessage());
        } catch (\Exception $e) {
            error_log("Error calling Gemini API: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el prompt optimizado para extracción de preguntas
     */
    private function getOcrPrompt(): string
    {
        return <<<PROMPT
Eres un experto en extraer preguntas y respuestas de documentos educativos escaneados.

INSTRUCCIONES ESPECÍFICAS:
1. Extrae TODAS las preguntas de opción múltiple que veas en la imagen
2. Identifica las opciones de respuesta (A, B, C, D, etc.)
3. La respuesta CORRECTA es la que está SUBRAYADA EN AMARILLO
4. Si no hay subrayado amarillo, busca otros indicadores como ✓, ✅, "correcto", etc.

FORMATO DE RESPUESTA REQUERIDO:
```
1. [Texto de la pregunta]
A. [Opción A]
B. [Opción B] (CORRECTA)  <- Marca así si está subrayada en amarillo
C. [Opción C]
D. [Opción D]

2. [Siguiente pregunta]
A. [Opción A]
B. [Opción B]
C. [Opción C] (CORRECTA)  <- Marca así si está subrayada en amarillo
D. [Opción D]
```

IMPORTANTE:
- Extrae el texto EXACTAMENTE como aparece
- Preserva la numeración original de las preguntas
- Marca como (CORRECTA) solo la opción subrayada en amarillo
- Si no ves subrayado amarillo, no marques ninguna como correcta
- Incluye TODAS las preguntas que veas en la imagen

Responde SOLO con el texto extraído en el formato especificado, sin comentarios adicionales.
PROMPT;
    }

    /**
     * Limpia y formatea el texto extraído
     */
    private function cleanExtractedText(string $text): string
    {
        // Remover caracteres no imprimibles
        $text = preg_replace('/[^\x20-\x7E\n\r\t]/', '', $text);
        // Normalizar espacios en blanco
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }

    /**
     * Parsea las preguntas del texto extraído
     */
    private function parseQuestionsFromText(string $text): array
    {
        $lines = explode("\n", $text);
        $questions = [];
        $currentQuestion = null;
        $currentAnswers = [];
        $correct = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Verificar si es una pregunta numerada
            if (preg_match('/^\d+\./', $line)) {
                // Guardar pregunta anterior si existe
                if ($currentQuestion) {
                    $questions[] = [
                        'question' => $currentQuestion,
                        'options' => $currentAnswers,
                        'correct_option' => $correct ?? 0,
                        'type' => 'multiple_choice',
                        'points' => 1
                    ];
                }

                // Iniciar nueva pregunta
                $currentQuestion = preg_replace('/^\d+\.\s*/', '', $line);
                $currentAnswers = [];
                $correct = null;
            }
            // Verificar si es una respuesta
            elseif (preg_match('/^[A-Z]\./', $line)) {
                $isCorrect = stripos($line, '(CORRECTA)') !== false || 
                            stripos($line, '(CORRECT)') !== false ||
                            stripos($line, '✓') !== false ||
                            stripos($line, '✅') !== false;
                
                // Limpiar el texto de la respuesta
                $text = preg_replace('/\(CORRECTA?\)/i', '', substr($line, 2));
                $text = preg_replace('/[✓✅]/', '', $text);
                $text = trim($text);
                
                if ($isCorrect) {
                    $correct = count($currentAnswers);
                }
                $currentAnswers[] = $text;
            }
        }

        // Agregar la última pregunta
        if ($currentQuestion) {
            $questions[] = [
                'question' => $currentQuestion,
                'options' => $currentAnswers,
                'correct_option' => $correct ?? 0,
                'type' => 'multiple_choice',
                'points' => 1
            ];
        }

        return $questions;
    }

    /**
     * Obtiene el tipo MIME de la imagen
     */
    private function getMimeType(string $imagePath): string
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];

        return $mimeTypes[$extension] ?? 'image/png';
    }
}