<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Smalot\PdfParser\Parser;

echo "=== PDF Structure Analysis ===\n\n";

// Check if the PDF file exists
$pdfPath = __DIR__ . '/../toledo_2020.pdf';
if (!file_exists($pdfPath)) {
    echo "❌ PDF file not found: {$pdfPath}\n";
    echo "Please place the toledo_2020.pdf file in the project root directory.\n";
    exit(1);
}

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    
    echo "✅ PDF loaded successfully!\n";
    echo "📄 File size: " . filesize($pdfPath) . " bytes\n";
    
    // Get detailed PDF information
    $details = $pdf->getDetails();
    echo "📄 PDF Details:\n";
    foreach ($details as $key => $value) {
        if (is_string($value) && !empty($value)) {
            echo "  {$key}: {$value}\n";
        }
    }
    
    // Get text content
    $text = $pdf->getText();
    echo "\n📄 Text extraction results:\n";
    echo "  Total text length: " . strlen($text) . " characters\n";
    
    if (strlen($text) === 0) {
        echo "⚠️  No text extracted! This PDF might be:\n";
        echo "  - Image-based (scanned document)\n";
        echo "  - Protected/encrypted\n";
        echo "  - Using custom fonts\n";
        echo "  - Have text in a different layer\n\n";
        
        // Try to get pages individually
        $pages = $pdf->getPages();
        echo "📄 PDF has " . count($pages) . " pages\n";
        
        if (count($pages) > 0) {
            echo "\n=== ANALYZING FIRST PAGE ===\n";
            $firstPage = $pages[0];
            $pageText = $firstPage->getText();
            echo "First page text length: " . strlen($pageText) . " characters\n";
            
            if (strlen($pageText) > 0) {
                echo "First page text (first 500 chars):\n";
                echo substr($pageText, 0, 500) . "\n";
            } else {
                echo "No text found on first page either.\n";
            }
            
            // Try to get page details
            $pageDetails = $firstPage->getDetails();
            echo "\nFirst page details:\n";
            foreach ($pageDetails as $key => $value) {
                if (is_string($value) && !empty($value)) {
                    echo "  {$key}: {$value}\n";
                }
            }
        }
        
        echo "\n=== RECOMMENDATIONS ===\n";
        echo "Since no text was extracted, you may need:\n";
        echo "1. OCR (Optical Character Recognition) for image-based PDFs\n";
        echo "2. Different PDF parsing approach\n";
        echo "3. Manual text extraction\n";
        echo "4. Check if PDF is password protected\n";
        
        exit(0);
    }
    
    // Split into lines
    $lines = explode("\n", $text);
    echo "📝 Total lines: " . count($lines) . "\n\n";
    
    // Show first 50 lines to understand structure
    echo "=== FIRST 50 LINES ===\n";
    for ($i = 0; $i < min(50, count($lines)); $i++) {
        $line = trim($lines[$i]);
        if (!empty($line)) {
            echo sprintf("Line %3d: %s\n", $i + 1, $line);
        }
    }
    
    echo "\n=== LINE ANALYSIS ===\n";
    $questionPatterns = [];
    $answerPatterns = [];
    $otherLines = [];
    
    foreach ($lines as $index => $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Check for question patterns
        if (preg_match('/^\d+\./', $line)) {
            $questionPatterns[] = [
                'line' => $index + 1,
                'text' => $line,
                'pattern' => 'numbered_question'
            ];
        } elseif (preg_match('/[¿?]/', $line)) {
            $questionPatterns[] = [
                'line' => $index + 1,
                'text' => $line,
                'pattern' => 'question_mark'
            ];
        } elseif (preg_match('/^(pregunta|question)/i', $line)) {
            $questionPatterns[] = [
                'line' => $index + 1,
                'text' => $line,
                'pattern' => 'question_keyword'
            ];
        }
        // Check for answer patterns
        elseif (preg_match('/^[A-Z][\.\)]\s/', $line)) {
            $answerPatterns[] = [
                'line' => $index + 1,
                'text' => $line,
                'pattern' => 'letter_answer'
            ];
        }
        // Other lines
        else {
            $otherLines[] = [
                'line' => $index + 1,
                'text' => $line,
                'length' => strlen($line)
            ];
        }
    }
    
    echo "🔍 Found " . count($questionPatterns) . " potential questions:\n";
    foreach ($questionPatterns as $q) {
        echo sprintf("  Line %3d (%s): %s\n", $q['line'], $q['pattern'], $q['text']);
    }
    
    echo "\n🔍 Found " . count($answerPatterns) . " potential answers:\n";
    foreach ($answerPatterns as $a) {
        echo sprintf("  Line %3d (%s): %s\n", $a['line'], $a['pattern'], $a['text']);
    }
    
    echo "\n🔍 Other lines (first 20):\n";
    for ($i = 0; $i < min(20, count($otherLines)); $i++) {
        $line = $otherLines[$i];
        echo sprintf("  Line %3d (%d chars): %s\n", $line['line'], $line['length'], $line['text']);
    }
    
    // Analyze line length distribution
    if (count($otherLines) > 0) {
        echo "\n=== LINE LENGTH ANALYSIS ===\n";
        $lengths = array_column($otherLines, 'length');
        $avgLength = array_sum($lengths) / count($lengths);
        $maxLength = max($lengths);
        $minLength = min($lengths);
        
        echo "Average line length: " . round($avgLength, 1) . " characters\n";
        echo "Maximum line length: {$maxLength} characters\n";
        echo "Minimum line length: {$minLength} characters\n";
    }
    
    // Suggest extraction patterns
    echo "\n=== EXTRACTION SUGGESTIONS ===\n";
    if (count($questionPatterns) > 0) {
        echo "✅ Questions detected! Suggested patterns:\n";
        echo "  - Numbered questions (1., 2., etc.)\n";
        echo "  - Lines with question marks (¿, ?)\n";
        echo "  - Lines with 'pregunta' or 'question' keywords\n";
    }
    
    if (count($answerPatterns) > 0) {
        echo "✅ Answers detected! Suggested patterns:\n";
        echo "  - Letter patterns (A., B., C., D.)\n";
        echo "  - Letter patterns with parentheses (A), B), C), D))\n";
    }
    
    echo "\n=== RECOMMENDED EXTRACTION LOGIC ===\n";
    echo "Based on the analysis, consider these improvements:\n";
    echo "1. Adjust question detection patterns\n";
    echo "2. Modify answer detection patterns\n";
    echo "3. Update line length thresholds\n";
    echo "4. Add specific keywords for this PDF format\n";
    
} catch (Exception $e) {
    echo "❌ Error analyzing PDF: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 