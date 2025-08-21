<?php

namespace App\Presentation\Controllers;

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateQuestionCommand;
use App\Application\Commands\CreateTopicCommand;
use App\Application\Commands\AssociateQuestionWithTopicCommand;
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;
use App\Domain\ValueObjects\Email;
use App\Domain\ValueObjects\Password;
use Smalot\PdfParser\Parser;

class PdfUploadController
{
    private Container $container;
    private Parser $pdfParser;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->pdfParser = new Parser();
    }

    public function uploadPdf(): void
    {
        header('Content-Type: application/json');
        
        try {
            // Check if file was uploaded
            if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No PDF file uploaded or upload error'
                ]);
                return;
            }

            $file = $_FILES['pdf_file'];
            
            // Validate file type
            if ($file['type'] !== 'application/pdf') {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Only PDF files are allowed'
                ]);
                return;
            }

            // Validate file size (max 10MB)
            if ($file['size'] > 10 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'File size too large. Maximum 10MB allowed'
                ]);
                return;
            }

            // Extract questions from PDF
            $questions = $this->extractQuestionsFromPdf($file['tmp_name']);
            
            // Return extracted questions
            echo json_encode([
                'success' => true,
                'data' => [
                    'questions' => $questions,
                    'total_questions' => count($questions)
                ]
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error processing PDF: ' . $e->getMessage()
            ]);
        }
    }

    public function importQuestions(): void
    {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['questions']) || !is_array($input['questions'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No questions data provided'
                ]);
                return;
            }

            $questions = $input['questions'];
            $topicTitle = $input['topic_title'] ?? 'Imported from PDF';
            $topicDescription = $input['topic_description'] ?? 'Questions imported from PDF upload';
            $topicLevel = $input['topic_level'] ?? 'intermediate';
            $examId = $input['exam_id'] ?? null;

            // Create topic if provided
            $topicId = null;
            if ($topicTitle) {
                $topicId = $this->createTopic($topicTitle, $topicDescription, $topicLevel);
            }

            $results = [
                'imported' => 0,
                'errors' => [],
                'topic_id' => $topicId
            ];

            // Import each question
            foreach ($questions as $questionData) {
                try {
                    $questionId = $this->createQuestion($questionData, $examId);
                    
                    // Associate with topic if created
                    if ($topicId && $questionId) {
                        $this->associateQuestionWithTopic($questionId, $topicId);
                    }
                    
                    $results['imported']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'question' => $questionData['question'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error importing questions: ' . $e->getMessage()
            ]);
        }
    }

    private function extractQuestionsFromPdf(string $pdfPath): array
    {
        $pdf = $this->pdfParser->parseFile($pdfPath);
        $details = $pdf->getDetails();
        
        // Check if it's a scanned document
        $isScanned = $this->isScannedDocument($details);
        
        if ($isScanned) {
            error_log("=== SCANNED PDF DETECTED ===");
            error_log("PDF Creator: " . ($details['Creator'] ?? 'Unknown'));
            error_log("PDF Producer: " . ($details['Producer'] ?? 'Unknown'));
            error_log("OCR processing required for scanned documents");
            
            // Require Gemini API key
            $apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
            if (!$apiKey) {
                return [[
                    'question' => 'No Gemini API key configured. Set GEMINI_API_KEY in environment.',
                    'options' => ['Contact administrator'],
                    'correct_option' => 0,
                    'type' => 'multiple_choice',
                    'points' => 1,
                    'note' => 'no_gemini_key'
                ]];
            }

            // Synchronous OCR extraction to return actual questions to the client
            require_once __DIR__ . '/../../Infrastructure/Services/GeminiOcrService.php';
            $ocrService = new \App\Infrastructure\Services\GeminiOcrService($apiKey);
            $rawQuestions = $ocrService->extractQuestionsFromPdf($pdfPath);

            // Normalize to required schema
            $normalized = array_values(array_filter(array_map(function ($q) {
                if (!is_array($q)) return null;
                $question = trim($q['question'] ?? '');
                $options = $q['options'] ?? [];
                $correct = $q['correct_option'] ?? 0;
                if ($question === '' || !is_array($options) || count($options) === 0) return null;
                // Bound correct option
                if (!is_int($correct) || $correct < 0 || $correct >= count($options)) {
                    $correct = 0;
                }
                return [
                    'question' => $question,
                    'options' => array_values(array_map('trim', $options)),
                    'correct_option' => $correct,
                    'type' => 'multiple_choice',
                    'points' => isset($q['points']) && is_int($q['points']) ? $q['points'] : 1,
                ];
            }, $rawQuestions)));

            return $normalized;
        }
        
        $text = $pdf->getText();
        
        // Debug: Log the raw extracted text
        error_log("=== PDF EXTRACTION DEBUG ===");
        error_log("Raw PDF text length: " . strlen($text));
        error_log("Raw PDF text (first 500 chars): " . substr($text, 0, 500));
        
        $lines = explode("\n", $text);
        error_log("Total lines extracted: " . count($lines));
        
        $questions = [];
        $currentQuestion = null;
        $currentAnswers = [];
        $lineNumber = 0;
        
        foreach ($lines as $line) {
            $lineNumber++;
            $originalLine = $line;
            $line = trim($line);
            
            // Debug: Log every non-empty line
            if (!empty($line)) {
                error_log("Line {$lineNumber}: '{$line}'");
                
                // Check if line is a question
                if ($this->isQuestion($line)) {
                    error_log("  -> IDENTIFIED AS QUESTION");
                    
                    // Save previous question if exists
                    if ($currentQuestion) {
                        error_log("  -> SAVING PREVIOUS QUESTION: '{$currentQuestion}' with " . count($currentAnswers) . " answers");
                        $questions[] = $this->formatQuestion($currentQuestion, $currentAnswers);
                    }
                    
                    // Start new question
                    $currentQuestion = $line;
                    $currentAnswers = [];
                    error_log("  -> STARTING NEW QUESTION: '{$currentQuestion}'");
                }
                // Check if line is an answer
                elseif ($this->isAnswer($line)) {
                    error_log("  -> IDENTIFIED AS ANSWER: '{$line}'");
                    $currentAnswers[] = $line;
                }
                else {
                    error_log("  -> UNCLASSIFIED LINE");
                }
            }
        }
        
        // Add last question
        if ($currentQuestion) {
            error_log("  -> SAVING FINAL QUESTION: '{$currentQuestion}' with " . count($currentAnswers) . " answers");
            $questions[] = $this->formatQuestion($currentQuestion, $currentAnswers);
        }
        
        error_log("Total questions extracted: " . count($questions));
        error_log("=== END PDF EXTRACTION DEBUG ===");
        
        return $questions;
    }
    
    private function isScannedDocument(array $details): bool
    {
        $scannedIndicators = [
            'ScanPDFMaker', 'Scanner', 'Scan', 'Adobe Acrobat Scan',
            'HP Scan', 'Canon Scan', 'Epson Scan', 'Samsung Scan'
        ];
        
        $creator = $details['Creator'] ?? '';
        $producer = $details['Producer'] ?? '';
        
        foreach ($scannedIndicators as $indicator) {
            if (stripos($creator, $indicator) !== false || 
                stripos($producer, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function isQuestion(string $line): bool
    {
        // Check for numbered questions (1., 2., etc.)
        if (preg_match('/^\d+\./', $line)) {
            error_log("    -> QUESTION: Numbered pattern detected");
            return true;
        }
        
        // Check for question keywords
        $questionKeywords = ['question', 'pregunta', '¿', '?'];
        foreach ($questionKeywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                error_log("    -> QUESTION: Keyword '{$keyword}' found");
                return true;
            }
        }
        
        // Check if line is longer than typical answer (likely a question)
        if (strlen($line) > 50) {
            error_log("    -> QUESTION: Long line (>50 chars)");
            return true;
        }
        
        error_log("    -> NOT A QUESTION: No patterns matched");
        return false;
    }

    private function isAnswer(string $line): bool
    {
        // Check for answer patterns (A., B., C., D., etc.)
        if (preg_match('/^[A-Z]\./', $line)) {
            error_log("    -> ANSWER: Pattern A. detected");
            return true;
        }
        
        if (preg_match('/^[A-Z]\)/', $line)) {
            error_log("    -> ANSWER: Pattern A) detected");
            return true;
        }
        
        error_log("    -> NOT AN ANSWER: No answer patterns matched");
        return false;
    }

    private function formatQuestion(string $question, array $answers): array
    {
        error_log("=== FORMATTING QUESTION ===");
        error_log("Original question: '{$question}'");
        error_log("Raw answers: " . json_encode($answers));
        
        // Clean up question text
        $question = preg_replace('/^\d+\.\s*/', '', $question);
        error_log("Cleaned question: '{$question}'");
        
        // Format answers
        $formattedAnswers = [];
        $correctAnswer = null;
        
        foreach ($answers as $answer) {
            $option = substr($answer, 0, 1); // A, B, C, D
            $text = trim(substr($answer, 2)); // Remove "A. " or "A) "
            
            error_log("Processing answer: '{$answer}' -> option: '{$option}', text: '{$text}'");
            
            $formattedAnswers[] = $text;
            
            // Try to identify correct answer (this is heuristic)
            if ($this->isCorrectAnswer($answer)) {
                $correctAnswer = count($formattedAnswers) - 1; // 0-based index
                error_log("Correct answer identified: option {$option} (index {$correctAnswer})");
            }
        }
        
        // If no correct answer identified, default to first option
        if ($correctAnswer === null && !empty($formattedAnswers)) {
            $correctAnswer = 0;
            error_log("No correct answer found, defaulting to first option (index 0)");
        }
        
        $result = [
            'question' => $question,
            'options' => $formattedAnswers,
            'correct_option' => $correctAnswer,
            'type' => 'multiple_choice',
            'points' => 1
        ];
        
        error_log("Final formatted question: " . json_encode($result));
        error_log("=== END FORMATTING QUESTION ===");
        
        return $result;
    }

    private function isCorrectAnswer(string $answer): bool
    {
        // Look for indicators of correct answer
        $correctIndicators = [
            'correct', 'correcto', '✓', '✅', 'right', 'derecho',
            'answer', 'respuesta', 'solution', 'solución'
        ];
        
        foreach ($correctIndicators as $indicator) {
            if (stripos($answer, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function createTopic(string $title, string $description, string $level): string
    {
        $command = new CreateTopicCommand(
            new TopicTitle($title),
            new TopicDescription($description),
            new TopicLevel($level)
        );
        
        $handler = $this->container->get(CreateTopicCommand::class);
        $result = $handler->handle($command);
        
        return $result->topicId->value();
    }

    private function createQuestion(array $questionData, ?string $examId): string
    {
        $command = new CreateQuestionCommand(
            new QuestionText($questionData['question']),
            new QuestionType($questionData['type']),
            $questionData['options'],
            $questionData['correct_option'],
            $questionData['points'],
            $examId
        );
        
        $handler = $this->container->get(CreateQuestionCommand::class);
        $result = $handler->handle($command);
        
        return $result->questionId->value();
    }

    private function associateQuestionWithTopic(string $questionId, string $topicId): void
    {
        $command = new AssociateQuestionWithTopicCommand($questionId, $topicId);
        $handler = $this->container->get(AssociateQuestionWithTopicCommand::class);
        $handler->handle($command);
    }
} 