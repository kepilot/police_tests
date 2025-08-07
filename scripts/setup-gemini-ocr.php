<?php

/**
 * Setup Gemini OCR Service
 * 
 * This script helps you configure and test the Gemini OCR service.
 */

echo "=== Gemini OCR Setup ===\n\n";

// Check if Gemini API key is set
$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
if (empty($apiKey)) {
    echo "❌ GEMINI_API_KEY environment variable is not set.\n\n";
    echo "📋 To set up Gemini OCR:\n\n";
    echo "1. Get your API key from Google AI Studio:\n";
    echo "   https://makersuite.google.com/app/apikey\n\n";
    echo "2. Add the API key to your environment:\n";
    echo "   - Add to env.local: GEMINI_API_KEY=your_api_key_here\n";
    echo "   - Or set as environment variable\n\n";
    echo "3. Restart your Docker containers:\n";
    echo "   docker-compose restart app\n\n";
    echo "4. Test with a scanned PDF:\n";
    echo "   Upload a scanned PDF through the admin panel\n\n";
    exit(1);
}

echo "✅ GEMINI_API_KEY is configured.\n\n";

// Test the service with a sample
echo "🧪 Testing Gemini OCR Service...\n\n";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../src/Infrastructure/Services/GeminiOcrService.php';
    
    $ocrService = new \App\Infrastructure\Services\GeminiOcrService($apiKey);
    
    echo "✅ Gemini OCR Service initialized successfully.\n\n";
    
    // Check if ImageMagick is available
    $output = [];
    $returnCode = 0;
    exec('convert --version', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ ImageMagick is installed and available.\n";
        echo "   Version: " . trim($output[0]) . "\n\n";
    } else {
        echo "❌ ImageMagick is not installed or not available.\n\n";
        echo "📋 To install ImageMagick:\n";
        echo "   - Ubuntu/Debian: sudo apt-get install imagemagick\n";
        echo "   - CentOS/RHEL: sudo yum install ImageMagick\n";
        echo "   - macOS: brew install imagemagick\n";
        echo "   - Windows: Download from https://imagemagick.org/\n\n";
        echo "   For Docker, add to Dockerfile:\n";
        echo "   RUN apt-get update && apt-get install -y imagemagick\n\n";
    }
    
    echo "🎯 Ready to process scanned PDFs!\n\n";
    echo "📝 Usage:\n";
    echo "1. Upload a scanned PDF through the admin panel\n";
    echo "2. The system will automatically detect it's scanned\n";
    echo "3. Gemini OCR will extract questions and answers\n";
    echo "4. Correct answers will be identified from yellow highlighting\n\n";
    
} catch (Exception $e) {
    echo "❌ Error testing Gemini OCR Service: " . $e->getMessage() . "\n";
    exit(1);
} 