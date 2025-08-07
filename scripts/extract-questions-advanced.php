<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;

echo "=== Advanced PDF Question Extraction ===\n\n";

// Check if the PDF file exists
$pdfPath = __DIR__ . '/../toledo_2020.pdf';
if (!file_exists($pdfPath)) {
    echo "❌ PDF file not found: {$pdfPath}\n";
    exit(1);
}

class AdvancedPdfExtractor
{
    private Parser $pdfParser;
    
    public function __construct()
    {
        $this->pdfParser = new Parser();
    }
    
    public function extractQuestions(string $pdfPath): array
    {
        echo "📄 Analyzing PDF: " . basename($pdfPath) . "\n";
        
        $pdf = $this->pdfParser->parseFile($pdfPath);
        $details = $pdf->getDetails();
        
        // Check if it's a scanned document
        $isScanned = $this->isScannedDocument($details);
        
        if ($isScanned) {
            echo "🔍 Detected scanned document - using OCR approach\n";
            return $this->extractFromScannedDocument($pdf);
        } else {
            echo "🔍 Detected text-based document - using direct extraction\n";
            return $this->extractFromTextDocument($pdf);
        }
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
    
    private function extractFromScannedDocument($pdf): array
    {
        echo "⚠️  OCR processing required for scanned documents\n";
        echo "📋 Manual extraction needed - here's the approach:\n\n";
        
        $pages = $pdf->getPages();
        echo "📄 Document has " . count($pages) . " pages\n";
        
        // For scanned documents, we need to provide manual extraction guidance
        $questions = $this->provideManualExtractionGuide($pages);
        
        return $questions;
    }
    
    private function extractFromTextDocument($pdf): array
    {
        $text = $pdf->getText();
        $lines = explode("\n", $text);
        
        echo "📝 Extracted " . count($lines) . " lines of text\n";
        
        $questions = [];
        $currentQuestion = null;
        $currentAnswers = [];
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line is a question
            if ($this->isQuestion($line)) {
                // Save previous question if exists
                if ($currentQuestion) {
                    $questions[] = $this->formatQuestion($currentQuestion, $currentAnswers);
                }
                
                // Start new question
                $currentQuestion = $line;
                $currentAnswers = [];
            }
            // Check if line is an answer
            elseif ($this->isAnswer($line)) {
                $currentAnswers[] = $line;
            }
        }
        
        // Add last question
        if ($currentQuestion) {
            $questions[] = $this->formatQuestion($currentQuestion, $currentAnswers);
        }
        
        return $questions;
    }
    
    private function provideManualExtractionGuide($pages): array
    {
        echo "=== MANUAL EXTRACTION GUIDE ===\n";
        echo "Since this is a scanned document, you'll need to:\n\n";
        
        echo "1. 📖 VISUAL ANALYSIS:\n";
        echo "   - Open the PDF in a PDF viewer\n";
        echo "   - Look for question patterns:\n";
        echo "     * Numbered questions (1., 2., 3., etc.)\n";
        echo "     * Question marks (¿, ?)\n";
        echo "     * Multiple choice options (A., B., C., D.)\n\n";
        
        echo "2. 🎯 IDENTIFY PATTERNS:\n";
        echo "   - Question format (e.g., '1. ¿Cuál es...?')\n";
        echo "   - Answer format (e.g., 'A. Option 1', 'B. Option 2')\n";
        echo "   - Correct answer indicators (✓, ✅, 'correcto', etc.)\n\n";
        
        echo "3. 📝 MANUAL EXTRACTION:\n";
        echo "   - Create a text file with the questions\n";
        echo "   - Use the format:\n";
        echo "     1. Question text?\n";
        echo "     A. Option 1\n";
        echo "     B. Option 2\n";
        echo "     C. Option 3\n";
        echo "     D. Option 4\n\n";
        
        echo "4. 🔧 IMPROVE EXTRACTION:\n";
        echo "   - Add OCR capabilities to the application\n";
        echo "   - Use services like Google Vision API or Tesseract\n";
        echo "   - Implement image preprocessing\n\n";
        
        // Return sample structure for manual input
        return [
            [
                'question' => 'Manual extraction required - see guide above',
                'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
                'correct_option' => 0,
                'type' => 'multiple_choice',
                'points' => 1,
                'note' => 'This is a placeholder. Manual extraction needed for scanned PDFs.'
            ]
        ];
    }
    
    private function isQuestion(string $line): bool
    {
        // Check for numbered questions (1., 2., etc.)
        if (preg_match('/^\d+\./', $line)) {
            return true;
        }
        
        // Check for question keywords
        $questionKeywords = ['question', 'pregunta', '¿', '?'];
        foreach ($questionKeywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                return true;
            }
        }
        
        // Check if line is longer than typical answer (likely a question)
        return strlen($line) > 50;
    }
    
    private function isAnswer(string $line): bool
    {
        // Check for answer patterns (A., B., C., D., etc.)
        return preg_match('/^[A-Z][\.\)]\s/', $line);
    }
    
    private function formatQuestion(string $question, array $answers): array
    {
        // Clean up question text
        $question = preg_replace('/^\d+\.\s*/', '', $question);
        
        // Format answers
        $formattedAnswers = [];
        $correctAnswer = null;
        
        foreach ($answers as $answer) {
            $option = substr($answer, 0, 1); // A, B, C, D
            $text = trim(substr($answer, 2)); // Remove "A. " or "A) "
            
            $formattedAnswers[] = $text;
            
            // Try to identify correct answer (this is heuristic)
            if ($this->isCorrectAnswer($answer)) {
                $correctAnswer = count($formattedAnswers) - 1; // 0-based index
            }
        }
        
        // If no correct answer identified, default to first option
        if ($correctAnswer === null && !empty($formattedAnswers)) {
            $correctAnswer = 0;
        }
        
        return [
            'question' => $question,
            'options' => $formattedAnswers,
            'correct_option' => $correctAnswer,
            'type' => 'multiple_choice',
            'points' => 1
        ];
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
}

// Run the extraction
try {
    $extractor = new AdvancedPdfExtractor();
    $questions = $extractor->extractQuestions($pdfPath);
    
    echo "\n=== EXTRACTION RESULTS ===\n";
    echo "Found " . count($questions) . " questions\n\n";
    
    foreach ($questions as $index => $question) {
        echo "Question " . ($index + 1) . ":\n";
        echo "  Q: " . $question['question'] . "\n";
        foreach ($question['options'] as $optionIndex => $option) {
            $marker = ($optionIndex === $question['correct_option']) ? "✓" : " ";
            echo "  {$marker} " . chr(65 + $optionIndex) . ". {$option}\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} 