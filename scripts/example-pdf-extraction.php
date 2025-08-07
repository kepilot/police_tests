<?php

/**
 * Ejemplo de uso de los scripts de extracción de preguntas desde PDF
 * 
 * Este script demuestra el flujo completo de trabajo:
 * 1. Extraer preguntas de un PDF
 * 2. Revisar los resultados
 * 3. Importar a la base de datos
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Ejemplo de Extracción de Preguntas desde PDF ===\n\n";

// Verificar que los scripts existen
$scripts = [
    'extract-questions-from-pdf.php',
    'extract-questions-advanced.php',
    'import-questions-from-pdf.php'
];

foreach ($scripts as $script) {
    if (!file_exists(__DIR__ . '/' . $script)) {
        echo "❌ Error: Script $script no encontrado\n";
        exit(1);
    }
}

echo "✅ Todos los scripts están disponibles\n\n";

// Verificar que la librería PDF está instalada
if (!class_exists('Smalot\PdfParser\Parser')) {
    echo "❌ Error: Librería smalot/pdfparser no está instalada\n";
    echo "Ejecuta: composer install\n";
    exit(1);
}

echo "✅ Librería PDF parser está instalada\n\n";

// Crear un PDF de ejemplo si no existe
$examplePdf = __DIR__ . '/example_questions.pdf';
if (!file_exists($examplePdf)) {
    echo "📝 Creando PDF de ejemplo...\n";
    createExamplePdf($examplePdf);
    echo "✅ PDF de ejemplo creado: $examplePdf\n\n";
} else {
    echo "✅ PDF de ejemplo ya existe: $examplePdf\n\n";
}

// Demostrar el flujo de trabajo
echo "=== Flujo de Trabajo Completo ===\n\n";

// Paso 1: Extraer preguntas con script básico
echo "Paso 1: Extraer preguntas (script básico)\n";
echo "Comando: php scripts/extract-questions-from-pdf.php $examplePdf json example_basic.json\n\n";

// Paso 2: Extraer preguntas con script avanzado
echo "Paso 2: Extraer preguntas (script avanzado)\n";
echo "Comando: php scripts/extract-questions-advanced.php $examplePdf report example_report.md\n\n";

// Paso 3: Importar a base de datos
echo "Paso 3: Importar a base de datos\n";
echo "Comando: php scripts/import-questions-from-pdf.php example_basic.json json \"Ejemplo Tema\" \"Preguntas de ejemplo\" intermediate\n\n";

echo "=== Instrucciones de Uso ===\n\n";

echo "1. **Para extraer preguntas de tu PDF:**\n";
echo "   php scripts/extract-questions-advanced.php tu_archivo.pdf report reporte.md\n\n";

echo "2. **Para revisar los resultados:**\n";
echo "   cat reporte.md\n\n";

echo "3. **Para importar a la base de datos:**\n";
echo "   php scripts/import-questions-from-pdf.php preguntas_output.json json \"Mi Tema\" \"Descripción\" intermediate\n\n";

echo "4. **Para verificar la importación:**\n";
echo "   cat import_report_*.md\n\n";

echo "=== Notas Importantes ===\n\n";
echo "- Los scripts funcionan mejor con PDFs que tienen texto claro y bien estructurado\n";
echo "- Las preguntas deben estar numeradas (1., 2., etc.) o en negrita\n";
echo "- Las respuestas deben comenzar con letras (A., B., C., D.)\n";
echo "- La detección de respuestas correctas es limitada, revisa manualmente\n";
echo "- Siempre prueba primero con un PDF pequeño antes de procesar muchos archivos\n\n";

echo "=== Documentación ===\n\n";
echo "Para más detalles, consulta: docs/PDF_QUESTION_EXTRACTION_GUIDE.md\n\n";

/**
 * Crear un PDF de ejemplo con preguntas
 */
function createExamplePdf($outputPath) {
    // Usar una librería simple para crear PDF o generar texto
    $content = "PREGUNTAS DE EJEMPLO\n\n";
    $content .= "1. ¿Cuál es la capital de Francia?\n";
    $content .= "   A. Londres\n";
    $content .= "   B. París\n";
    $content .= "   C. Madrid\n";
    $content .= "   D. Roma\n\n";
    
    $content .= "2. ¿En qué año comenzó la Segunda Guerra Mundial?\n";
    $content .= "   A. 1939\n";
    $content .= "   B. 1940\n";
    $content .= "   C. 1941\n";
    $content .= "   D. 1942\n\n";
    
    $content .= "3. ¿Cuál es el planeta más grande del sistema solar?\n";
    $content .= "   A. Tierra\n";
    $content .= "   B. Marte\n";
    $content .= "   C. Júpiter\n";
    $content .= "   D. Saturno\n\n";
    
    $content .= "4. ¿Quién escribió 'Don Quijote'?\n";
    $content .= "   A. Miguel de Cervantes\n";
    $content .= "   B. William Shakespeare\n";
    $content .= "   C. Dante Alighieri\n";
    $content .= "   D. Johann Wolfgang von Goethe\n\n";
    
    $content .= "5. ¿Cuál es el elemento químico más abundante en el universo?\n";
    $content .= "   A. Helio\n";
    $content .= "   B. Carbono\n";
    $content .= "   C. Oxígeno\n";
    $content .= "   D. Hidrógeno\n";
    
    // Crear un archivo de texto simple (no PDF real, pero suficiente para demostración)
    file_put_contents($outputPath . '.txt', $content);
    
    echo "   Nota: Se creó un archivo de texto (.txt) en lugar de PDF para la demostración.\n";
    echo "   Para usar con PDFs reales, coloca tu archivo PDF en la carpeta scripts/ y ejecuta los comandos.\n";
}

echo "✅ Ejemplo completado. Revisa los archivos generados para ver el resultado.\n"; 