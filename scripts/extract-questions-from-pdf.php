<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$envFile = __DIR__ . '/../env.local';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../.env';
}
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use Smalot\PdfParser\Parser;

class QuestionExtractor
{
    private $parser;
    
    public function __construct()
    {
        $this->parser = new Parser();
    }
    
    /**
     * Extract questions from a PDF file
     * 
     * @param string $pdfPath Path to the PDF file
     * @return array Array of extracted questions with their answers
     */
    public function extractQuestions($pdfPath)
    {
        if (!file_exists($pdfPath)) {
            throw new Exception("PDF file not found: $pdfPath");
        }
        
        echo "Processing PDF: $pdfPath\n";
        
        // Parse the PDF
        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();
        
        // Split text into lines
        $lines = explode("\n", $text);
        
        $questions = [];
        $currentQuestion = null;
        $currentAnswers = [];
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            
            // Skip empty lines
            if (empty($line)) {
                continue;
            }
            
            // Check if this line is a question (bold text - usually appears as stronger/cleaner text)
            if ($this->isQuestion($line)) {
                // Save previous question if exists
                if ($currentQuestion !== null) {
                    $questions[] = [
                        'question' => $currentQuestion,
                        'answers' => $currentAnswers,
                        'correct_answer' => $this->identifyCorrectAnswer($currentAnswers)
                    ];
                }
                
                // Start new question
                $currentQuestion = $line;
                $currentAnswers = [];
            } 
            // Check if this line is an answer option
            elseif ($this->isAnswer($line)) {
                $currentAnswers[] = $line;
            }
            // If it's not a question or answer, it might be part of the current question
            elseif ($currentQuestion !== null) {
                $currentQuestion .= ' ' . $line;
            }
        }
        
        // Don't forget the last question
        if ($currentQuestion !== null) {
            $questions[] = [
                'question' => $currentQuestion,
                'answers' => $currentAnswers,
                'correct_answer' => $this->identifyCorrectAnswer($currentAnswers)
            ];
        }
        
        return $questions;
    }
    
    /**
     * Determine if a line is a question
     * 
     * @param string $line
     * @return bool
     */
    private function isQuestion($line)
    {
        // Questions are typically:
        // 1. Start with numbers followed by dot or parenthesis
        // 2. Are longer than typical answer options
        // 3. Don't start with answer letters (A, B, C, D)
        
        $line = trim($line);
        
        // Check if it starts with a number followed by dot or parenthesis
        if (preg_match('/^\d+[\.\)]\s/', $line)) {
            return true;
        }
        
        // Check if it's a longer line that doesn't start with answer letters
        if (strlen($line) > 50 && !preg_match('/^[A-D][\.\)]\s/', $line)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Determine if a line is an answer option
     * 
     * @param string $line
     * @return bool
     */
    private function isAnswer($line)
    {
        $line = trim($line);
        
        // Answer options typically start with A, B, C, D followed by dot or parenthesis
        return preg_match('/^[A-D][\.\)]\s/', $line);
    }
    
    /**
     * Identify the correct answer (highlighted in yellow)
     * Since we can't detect highlighting from text extraction,
     * we'll need to use heuristics or manual input
     * 
     * @param array $answers
     * @return string|null
     */
    private function identifyCorrectAnswer($answers)
    {
        // For now, we'll return null as we can't detect highlighting from text
        // In a real implementation, you might:
        // 1. Use PDF parsing libraries that can detect highlighting
        // 2. Ask user to manually specify correct answers
        // 3. Use AI to analyze the content
        
        return null;
    }
    
    /**
     * Export questions to JSON format
     * 
     * @param array $questions
     * @param string $outputPath
     */
    public function exportToJson($questions, $outputPath)
    {
        $json = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($outputPath, $json);
        echo "Questions exported to: $outputPath\n";
    }
    
    /**
     * Export questions to CSV format
     * 
     * @param array $questions
     * @param string $outputPath
     */
    public function exportToCsv($questions, $outputPath)
    {
        $handle = fopen($outputPath, 'w');
        
        // Write header
        fputcsv($handle, ['Question', 'Answer A', 'Answer B', 'Answer C', 'Answer D', 'Correct Answer']);
        
        // Write questions
        foreach ($questions as $question) {
            $answers = array_pad($question['answers'], 4, ''); // Ensure we have 4 answers
            $row = [
                $question['question'],
                $answers[0] ?? '',
                $answers[1] ?? '',
                $answers[2] ?? '',
                $answers[3] ?? '',
                $question['correct_answer'] ?? ''
            ];
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        echo "Questions exported to: $outputPath\n";
    }
}

// Main execution
if ($argc < 2) {
    echo "Usage: php extract-questions-from-pdf.php <pdf_file_path> [output_format] [output_file]\n";
    echo "  pdf_file_path: Path to the PDF file to process\n";
    echo "  output_format: json or csv (default: json)\n";
    echo "  output_file: Output file path (default: questions_output.json/csv)\n";
    echo "\nExample: php extract-questions-from-pdf.php questions.pdf json questions.json\n";
    exit(1);
}

$pdfPath = $argv[1];
$outputFormat = $argv[2] ?? 'json';
$outputFile = $argv[3] ?? null;

try {
    $extractor = new QuestionExtractor();
    $questions = $extractor->extractQuestions($pdfPath);
    
    echo "\nExtracted " . count($questions) . " questions:\n\n";
    
    foreach ($questions as $index => $question) {
        echo "Question " . ($index + 1) . ":\n";
        echo "  " . $question['question'] . "\n";
        
        if (!empty($question['answers'])) {
            echo "  Answers:\n";
            foreach ($question['answers'] as $answer) {
                echo "    " . $answer . "\n";
            }
        }
        
        if ($question['correct_answer']) {
            echo "  Correct Answer: " . $question['correct_answer'] . "\n";
        }
        
        echo "\n";
    }
    
    // Export to file
    if ($outputFile === null) {
        $outputFile = 'questions_output.' . $outputFormat;
    }
    
    if ($outputFormat === 'csv') {
        $extractor->exportToCsv($questions, $outputFile);
    } else {
        $extractor->exportToJson($questions, $outputFile);
    }
    
    echo "\n✅ Question extraction completed successfully!\n";
    echo "Note: Correct answers (highlighted in yellow) cannot be automatically detected.\n";
    echo "You may need to manually review and specify the correct answers.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 