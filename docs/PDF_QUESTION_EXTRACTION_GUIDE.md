# Guía de Extracción de Preguntas desde PDF

Esta guía explica cómo extraer preguntas de documentos PDF que contienen preguntas en negrita, respuestas a continuación, y respuestas correctas subrayadas en amarillo.

## Requisitos Previos

1. **Instalar dependencias**: El proyecto necesita la librería `smalot/pdfparser` para procesar PDFs.

```bash
composer install
```

2. **Configurar base de datos**: Asegúrate de que la base de datos esté configurada y las migraciones ejecutadas.

```bash
php scripts/setup-database-local.php
```

## Scripts Disponibles

### 1. `extract-questions-from-pdf.php` - Extracción Básica

Script básico para extraer preguntas de PDFs.

**Uso:**
```bash
php scripts/extract-questions-from-pdf.php <archivo_pdf> [formato_salida] [archivo_salida]
```

**Parámetros:**
- `archivo_pdf`: Ruta al archivo PDF a procesar
- `formato_salida`: `json` o `csv` (por defecto: `json`)
- `archivo_salida`: Ruta del archivo de salida (opcional)

**Ejemplos:**
```bash
# Extraer a JSON
php scripts/extract-questions-from-pdf.php preguntas.pdf json preguntas.json

# Extraer a CSV
php scripts/extract-questions-from-pdf.php preguntas.pdf csv preguntas.csv

# Usar nombres por defecto
php scripts/extract-questions-from-pdf.php preguntas.pdf
```

### 2. `extract-questions-advanced.php` - Extracción Avanzada

Script avanzado con mejor detección de formato y resaltado.

**Uso:**
```bash
php scripts/extract-questions-advanced.php <archivo_pdf> [formato_salida] [archivo_salida]
```

**Parámetros:**
- `archivo_pdf`: Ruta al archivo PDF a procesar
- `formato_salida`: `json`, `csv`, o `report` (por defecto: `json`)
- `archivo_salida`: Ruta del archivo de salida (opcional)

**Formatos de salida:**
- `json`: Archivo JSON con preguntas y respuestas
- `csv`: Archivo CSV con columnas separadas
- `report`: Reporte en Markdown con análisis detallado

**Ejemplos:**
```bash
# Generar reporte detallado
php scripts/extract-questions-advanced.php preguntas.pdf report reporte_preguntas.md

# Extraer a CSV con detección avanzada
php scripts/extract-questions-advanced.php preguntas.pdf csv preguntas_avanzadas.csv
```

### 3. `import-questions-from-pdf.php` - Importar a Base de Datos

Script para importar las preguntas extraídas directamente a la base de datos del sistema.

**Uso:**
```bash
php scripts/import-questions-from-pdf.php <archivo_entrada> <tipo_archivo> [titulo_tema] [descripcion_tema] [nivel_tema]
```

**Parámetros:**
- `archivo_entrada`: Archivo JSON o CSV con preguntas extraídas
- `tipo_archivo`: `json` o `csv`
- `titulo_tema`: Título del tema para las preguntas (opcional)
- `descripcion_tema`: Descripción del tema (opcional)
- `nivel_tema`: Nivel del tema (`beginner`, `intermediate`, `advanced`) (por defecto: `intermediate`)

**Ejemplos:**
```bash
# Importar desde JSON con tema
php scripts/import-questions-from-pdf.php preguntas.json json "Matemáticas Básicas" "Preguntas de matemáticas" intermediate

# Importar desde CSV sin tema específico
php scripts/import-questions-from-pdf.php preguntas.csv csv

# Importar con tema avanzado
php scripts/import-questions-from-pdf.php preguntas.json json "Historia" "Preguntas de historia mundial" advanced
```

## Flujo de Trabajo Completo

### Paso 1: Extraer Preguntas del PDF

```bash
# Extraer preguntas con detección avanzada
php scripts/extract-questions-advanced.php documento.pdf report reporte_inicial.md

# Revisar el reporte generado
cat reporte_inicial.md
```

### Paso 2: Ajustar si es Necesario

Si la extracción no es perfecta, puedes:

1. **Revisar el reporte** para ver qué preguntas se detectaron correctamente
2. **Editar manualmente** el archivo JSON o CSV si es necesario
3. **Revisar las respuestas correctas** detectadas automáticamente

### Paso 3: Importar a la Base de Datos

```bash
# Importar las preguntas al sistema
php scripts/import-questions-from-pdf.php preguntas_output.json json "Mi Tema" "Descripción del tema" intermediate
```

### Paso 4: Verificar la Importación

```bash
# Revisar el reporte de importación
cat import_report_*.md
```

## Formatos de Entrada Esperados

### Estructura del PDF

Los scripts están diseñados para trabajar con PDFs que tienen:

1. **Preguntas en negrita** o con numeración (1., 2., etc.)
2. **Respuestas** que comienzan con letras (A., B., C., D.)
3. **Respuestas correctas** subrayadas en amarillo o marcadas de alguna forma

### Ejemplo de PDF Esperado

```
1. ¿Cuál es la capital de Francia?
   A. Londres
   B. París
   C. Madrid
   D. Roma

2. ¿En qué año comenzó la Segunda Guerra Mundial?
   A. 1939
   B. 1940
   C. 1941
   D. 1942
```

## Formatos de Salida

### JSON

```json
[
  {
    "question": "¿Cuál es la capital de Francia?",
    "answers": [
      {
        "text": "A. Londres",
        "is_highlighted": false
      },
      {
        "text": "B. París",
        "is_highlighted": true
      },
      {
        "text": "C. Madrid",
        "is_highlighted": false
      },
      {
        "text": "D. Roma",
        "is_highlighted": false
      }
    ],
    "correct_answer": "B. París"
  }
]
```

### CSV

```csv
Question,Answer A,Answer B,Answer C,Answer D,Correct Answer,Highlighted Answer
"¿Cuál es la capital de Francia?","A. Londres","B. París","C. Madrid","D. Roma","B. París","B. París"
```

## Limitaciones y Consideraciones

### Detección de Resaltado

- **Limitación**: Los scripts no pueden detectar perfectamente el resaltado en amarillo desde el texto extraído
- **Solución**: Usan heurísticas basadas en palabras clave como "CORRECT", "✓", etc.
- **Recomendación**: Revisar manualmente las respuestas correctas detectadas

### Formato del PDF

- **Mejor resultado**: PDFs con texto claro y bien estructurado
- **Problemas**: PDFs escaneados, con imágenes, o formato complejo
- **Solución**: Usar OCR si es necesario antes de la extracción

### Preguntas Complejas

- **Soporte**: Preguntas de opción múltiple estándar
- **Limitación**: Preguntas con imágenes, fórmulas matemáticas complejas
- **Solución**: Editar manualmente después de la extracción

## Solución de Problemas

### Error: "PDF file not found"

```bash
# Verificar que el archivo existe
ls -la documento.pdf

# Usar ruta absoluta si es necesario
php scripts/extract-questions-from-pdf.php /ruta/completa/documento.pdf
```

### Error: "Invalid JSON format"

```bash
# Verificar que el archivo JSON es válido
php -r "json_decode(file_get_contents('preguntas.json')); echo json_last_error_msg();"
```

### Error: "Could not create topic"

```bash
# Verificar que la base de datos está configurada
php scripts/setup-database-local.php

# Verificar conexión a la base de datos
php scripts/check-table-structure.php
```

### Preguntas no detectadas correctamente

1. **Revisar el formato del PDF**: Asegúrate de que las preguntas tengan numeración clara
2. **Usar el script avanzado**: `extract-questions-advanced.php` tiene mejor detección
3. **Editar manualmente**: Modifica el archivo JSON/CSV antes de importar

## Mejores Prácticas

1. **Probar primero**: Siempre ejecuta la extracción en un PDF de prueba
2. **Revisar resultados**: Revisa el reporte antes de importar
3. **Hacer respaldo**: Haz una copia de seguridad antes de importar grandes cantidades
4. **Usar temas**: Organiza las preguntas en temas para mejor gestión
5. **Verificar importación**: Revisa el reporte de importación para detectar errores

## Integración con el Sistema

Las preguntas importadas se integran completamente con el sistema existente:

- **Entidades**: Se crean como entidades `Question` en el dominio
- **Temas**: Se pueden asociar con `Topic` existentes o crear nuevos
- **Exámenes**: Se pueden usar para crear exámenes automáticamente
- **Aprendizaje**: Están disponibles en el portal de aprendizaje

## Scripts de Utilidad Adicionales

### Verificar Estructura de Base de Datos

```bash
php scripts/check-table-structure.php
```

### Crear Usuario Administrador

```bash
php scripts/create-default-admin.php
```

### Ejecutar Migraciones

```bash
php scripts/run-migrations.php
```

## Soporte

Si encuentras problemas:

1. **Revisar logs**: Los scripts muestran información detallada durante la ejecución
2. **Verificar formato**: Asegúrate de que el PDF tenga el formato esperado
3. **Probar con archivo simple**: Usa un PDF simple para verificar que funciona
4. **Revisar documentación**: Consulta esta guía y otros documentos del proyecto 