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

use App\Infrastructure\Container\Container;
use App\Application\Commands\CreateQuestionCommand;
use App\Application\Commands\CreateTopicCommand;
use App\Application\Commands\AssociateQuestionWithTopicCommand;
use App\Domain\ValueObjects\QuestionText;
use App\Domain\ValueObjects\QuestionType;
use App\Domain\ValueObjects\TopicTitle;
use App\Domain\ValueObjects\TopicDescription;
use App\Domain\ValueObjects\TopicLevel;

class QuestionImporter
{
    private $container;
    private $questionHandler;
    private $topicHandler;
    private $associateHandler;
    
    public function __construct()
    {
        $this->container = new Container();
        $this->questionHandler = $this->container->get(\App\Application\Commands\CreateQuestionHandler::class);
        $this->topicHandler = $this->container->get(\App\Application\Commands\CreateTopicHandler::class);
        $this->associateHandler = $this->container->get(\App\Application\Commands\AssociateQuestionWithTopicHandler::class);
    }
    
    /**
     * Import questions from a JSON file into the database
     * 
     * @param string $jsonFile Path to the JSON file with extracted questions
     * @param string $topicTitle Optional topic title for the questions
     * @param string $topicDescription Optional topic description
     * @param string $topicLevel Optional topic level (beginner, intermediate, advanced)
     * @return array Import results
     */
    public function importFromJson($jsonFile, $topicTitle = null, $topicDescription = null, $topicLevel = 'intermediate')
    {
        if (!file_exists($jsonFile)) {
            throw new Exception("JSON file not found: $jsonFile");
        }
        
        $jsonContent = file_get_contents($jsonFile);
        $questions = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format: " . json_last_error_msg());
        }
        
        echo "Importing " . count($questions) . " questions from: $jsonFile\n\n";
        
        $results = [
            'total' => count($questions),
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Create topic if provided
        $topicId = null;
        if ($topicTitle) {
            try {
                $topicCommand = new CreateTopicCommand(
                    $topicTitle,
                    $topicDescription ?? "Questions imported from PDF",
                    $topicLevel
                );
                
                $topic = $this->topicHandler($topicCommand);
                $topicId = $topic->getId()->toString();
                
                echo "Created topic: $topicTitle (ID: $topicId)\n\n";
            } catch (Exception $e) {
                echo "Warning: Could not create topic: " . $e->getMessage() . "\n";
            }
        }
        
        // Import questions
        foreach ($questions as $index => $questionData) {
            try {
                echo "Importing question " . ($index + 1) . "...\n";
                
                // Prepare question text with answers
                $questionText = $this->formatQuestionWithAnswers($questionData);
                
                // Create question command
                $command = new CreateQuestionCommand(
                    $questionText,
                    'multiple_choice' // Default type for PDF questions
                );
                
                $question = $this->questionHandler($command);
                
                // Associate with topic if available
                if ($topicId) {
                    try {
                        $associateCommand = new AssociateQuestionWithTopicCommand(
                            $question->getId()->toString(),
                            $topicId
                        );
                        
                        $this->associateHandler($associateCommand);
                        echo "  Associated with topic: $topicTitle\n";
                    } catch (Exception $e) {
                        echo "  Warning: Could not associate with topic: " . $e->getMessage() . "\n";
                    }
                }
                
                $results['imported']++;
                echo "  ✅ Question imported successfully (ID: " . $question->getId()->toString() . ")\n\n";
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'question_index' => $index,
                    'error' => $e->getMessage()
                ];
                
                echo "  ❌ Failed to import question: " . $e->getMessage() . "\n\n";
            }
        }
        
        return $results;
    }
    
    /**
     * Import questions from a CSV file into the database
     * 
     * @param string $csvFile Path to the CSV file with extracted questions
     * @param string $topicTitle Optional topic title for the questions
     * @param string $topicDescription Optional topic description
     * @param string $topicLevel Optional topic level
     * @return array Import results
     */
    public function importFromCsv($csvFile, $topicTitle = null, $topicDescription = null, $topicLevel = 'intermediate')
    {
        if (!file_exists($csvFile)) {
            throw new Exception("CSV file not found: $csvFile");
        }
        
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            throw new Exception("Could not open CSV file: $csvFile");
        }
        
        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            throw new Exception("Could not read CSV header");
        }
        
        $questions = [];
        $rowNumber = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            if (count($row) < 6) {
                echo "Warning: Skipping row $rowNumber (insufficient columns)\n";
                continue;
            }
            
            $questions[] = [
                'question' => $row[0],
                'answer_a' => $row[1],
                'answer_b' => $row[2],
                'answer_c' => $row[3],
                'answer_d' => $row[4],
                'correct_answer' => $row[5],
                'highlighted_answer' => $row[6] ?? ''
            ];
        }
        
        fclose($handle);
        
        echo "Importing " . count($questions) . " questions from: $csvFile\n\n";
        
        $results = [
            'total' => count($questions),
            'imported' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Create topic if provided
        $topicId = null;
        if ($topicTitle) {
            try {
                $topicCommand = new CreateTopicCommand(
                    $topicTitle,
                    $topicDescription ?? "Questions imported from CSV",
                    $topicLevel
                );
                
                $topic = $this->topicHandler($topicCommand);
                $topicId = $topic->getId()->toString();
                
                echo "Created topic: $topicTitle (ID: $topicId)\n\n";
            } catch (Exception $e) {
                echo "Warning: Could not create topic: " . $e->getMessage() . "\n";
            }
        }
        
        // Import questions
        foreach ($questions as $index => $questionData) {
            try {
                echo "Importing question " . ($index + 1) . "...\n";
                
                // Prepare question text with answers
                $questionText = $this->formatQuestionFromCsv($questionData);
                
                // Create question command
                $command = new CreateQuestionCommand(
                    $questionText,
                    'multiple_choice'
                );
                
                $question = $this->questionHandler($command);
                
                // Associate with topic if available
                if ($topicId) {
                    try {
                        $associateCommand = new AssociateQuestionWithTopicCommand(
                            $question->getId()->toString(),
                            $topicId
                        );
                        
                        $this->associateHandler($associateCommand);
                        echo "  Associated with topic: $topicTitle\n";
                    } catch (Exception $e) {
                        echo "  Warning: Could not associate with topic: " . $e->getMessage() . "\n";
                    }
                }
                
                $results['imported']++;
                echo "  ✅ Question imported successfully (ID: " . $question->getId()->toString() . ")\n\n";
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'question_index' => $index,
                    'error' => $e->getMessage()
                ];
                
                echo "  ❌ Failed to import question: " . $e->getMessage() . "\n\n";
            }
        }
        
        return $results;
    }
    
    /**
     * Format question with answers for JSON import
     * 
     * @param array $questionData
     * @return string
     */
    private function formatQuestionWithAnswers($questionData)
    {
        $text = $questionData['question'] . "\n\n";
        
        if (!empty($questionData['answers'])) {
            $text .= "Opciones:\n";
            foreach ($questionData['answers'] as $answer) {
                $highlight = $answer['is_highlighted'] ? " (CORRECTA)" : "";
                $text .= "- " . $answer['text'] . $highlight . "\n";
            }
        }
        
        if ($questionData['correct_answer']) {
            $text .= "\nRespuesta correcta: " . $questionData['correct_answer'];
        }
        
        return $text;
    }
    
    /**
     * Format question with answers for CSV import
     * 
     * @param array $questionData
     * @return string
     */
    private function formatQuestionFromCsv($questionData)
    {
        $text = $questionData['question'] . "\n\n";
        
        $answers = [
            'A' => $questionData['answer_a'],
            'B' => $questionData['answer_b'],
            'C' => $questionData['answer_c'],
            'D' => $questionData['answer_d']
        ];
        
        $text .= "Opciones:\n";
        foreach ($answers as $letter => $answer) {
            if (!empty($answer)) {
                $isCorrect = ($answer === $questionData['correct_answer']) || 
                            ($answer === $questionData['highlighted_answer']);
                $highlight = $isCorrect ? " (CORRECTA)" : "";
                $text .= "$letter) " . $answer . $highlight . "\n";
            }
        }
        
        if ($questionData['correct_answer']) {
            $text .= "\nRespuesta correcta: " . $questionData['correct_answer'];
        }
        
        return $text;
    }
    
    /**
     * Generate import report
     * 
     * @param array $results
     * @param string $outputPath
     */
    public function generateImportReport($results, $outputPath)
    {
        $report = "# Question Import Report\n\n";
        $report .= "## Summary\n";
        $report .= "- Total questions processed: " . $results['total'] . "\n";
        $report .= "- Successfully imported: " . $results['imported'] . "\n";
        $report .= "- Failed imports: " . $results['failed'] . "\n";
        $report .= "- Success rate: " . round(($results['imported'] / $results['total']) * 100, 2) . "%\n\n";
        
        if (!empty($results['errors'])) {
            $report .= "## Errors\n\n";
            foreach ($results['errors'] as $error) {
                $report .= "- Question " . ($error['question_index'] + 1) . ": " . $error['error'] . "\n";
            }
            $report .= "\n";
        }
        
        file_put_contents($outputPath, $report);
        echo "Import report generated: $outputPath\n";
    }
}

// Main execution
if ($argc < 3) {
    echo "Usage: php import-questions-from-pdf.php <input_file> <file_type> [topic_title] [topic_description] [topic_level]\n";
    echo "  input_file: Path to the JSON or CSV file with extracted questions\n";
    echo "  file_type: json or csv\n";
    echo "  topic_title: Optional topic title for the questions\n";
    echo "  topic_description: Optional topic description\n";
    echo "  topic_level: Optional topic level (beginner, intermediate, advanced)\n";
    echo "\nExample: php import-questions-from-pdf.php questions.json json \"Matemáticas Básicas\" \"Preguntas de matemáticas\" intermediate\n";
    echo "Example: php import-questions-from-pdf.php questions.csv csv \"Historia\" \"Preguntas de historia\"\n";
    exit(1);
}

$inputFile = $argv[1];
$fileType = strtolower($argv[2]);
$topicTitle = $argv[3] ?? null;
$topicDescription = $argv[4] ?? null;
$topicLevel = $argv[5] ?? 'intermediate';

try {
    $importer = new QuestionImporter();
    
    if ($fileType === 'json') {
        $results = $importer->importFromJson($inputFile, $topicTitle, $topicDescription, $topicLevel);
    } elseif ($fileType === 'csv') {
        $results = $importer->importFromCsv($inputFile, $topicTitle, $topicDescription, $topicLevel);
    } else {
        throw new Exception("Unsupported file type: $fileType. Use 'json' or 'csv'.");
    }
    
    // Generate report
    $reportFile = 'import_report_' . date('Y-m-d_H-i-s') . '.md';
    $importer->generateImportReport($results, $reportFile);
    
    echo "\n✅ Question import completed!\n";
    echo "Summary:\n";
    echo "  - Total processed: " . $results['total'] . "\n";
    echo "  - Successfully imported: " . $results['imported'] . "\n";
    echo "  - Failed: " . $results['failed'] . "\n";
    echo "  - Success rate: " . round(($results['imported'] / $results['total']) * 100, 2) . "%\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 