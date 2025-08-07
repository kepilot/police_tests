# 🚀 Sistema de Colas RabbitMQ para OCR de PDFs

## 📋 Resumen Ejecutivo

Este documento describe la implementación de un sistema de colas asíncrono usando RabbitMQ para procesar PDFs escaneados con OCR usando Google Gemini Vision API. El sistema permite procesar múltiples PDFs simultáneamente sin bloquear la aplicación web.

## 🏗️ Arquitectura del Sistema

### Diagrama de Flujo
```
Usuario sube PDF → API → RabbitMQ → Workers → Gemini API → Base de datos
                ↓
            Respuesta inmediata: "PDF en cola para procesamiento"
```

### Componentes Principales

1. **QueueService** (`src/Infrastructure/Services/QueueService.php`)
   - Maneja la conexión con RabbitMQ
   - Publica y consume mensajes de las colas
   - Configura exchanges y dead letter queues

2. **GeminiOcrService** (`src/Infrastructure/Services/GeminiOcrService.php`)
   - Procesa PDFs escaneados usando Gemini Vision API
   - Convierte PDFs a imágenes usando ImageMagick
   - Extrae preguntas y respuestas con detección de respuestas correctas

3. **Workers** (`scripts/workers/`)
   - `pdf-ocr-worker.php`: Convierte PDFs a imágenes
   - `gemini-api-worker.php`: Procesa imágenes con Gemini API
   - `results-worker.php`: Guarda resultados y envía notificaciones

## 📊 Estructura de Colas

### Colas Principales

| Cola | Propósito | Workers |
|------|-----------|---------|
| `pdf_ocr_queue` | Convierte PDFs a imágenes | pdf-ocr-worker |
| `gemini_api_queue` | Procesa imágenes con Gemini API | gemini-api-worker |
| `results_queue` | Guarda resultados finales | results-worker |
| `dead_letter_queue` | Mensajes que fallaron | - |

### Exchanges

| Exchange | Tipo | Routing Key |
|----------|------|-------------|
| `pdf_ocr_exchange` | direct | `pdf_ocr` |
| `gemini_api_exchange` | direct | `gemini_api` |
| `results_exchange` | direct | `results` |
| `dead_letter_exchange` | direct | `dead_letter` |

## 🔄 Flujo de Procesamiento

### 1. Subida de PDF
```php
// El usuario sube un PDF escaneado
$result = $ocrService->processPdfAsync($pdfPath, $jobId, $userId);
// Retorna inmediatamente: {"success": true, "job_id": "abc123", "status": "queued"}
```

### 2. Procesamiento de PDF OCR
```php
// pdf-ocr-worker.php procesa el trabajo
$ocrService->processPdfOcrJob($message);
// Convierte PDF a imágenes y encola trabajos de Gemini API
```

### 3. Procesamiento de Gemini API
```php
// gemini-api-worker.php procesa cada imagen
$ocrService->processGeminiApiJob($message);
// Extrae texto usando Gemini Vision API
```

### 4. Procesamiento de Resultados
```php
// results-worker.php guarda los resultados
$ocrService->processResultsJob($message);
// Guarda en BD y envía notificaciones
```

## 🛠️ Configuración

### 1. Variables de Entorno
```bash
# Gemini AI
GEMINI_API_KEY=your_gemini_api_key_here

# RabbitMQ
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASS=admin123
```

### 2. Dependencias
```json
{
    "guzzlehttp/guzzle": "^7.0",
    "php-amqplib/php-amqplib": "^3.0"
}
```

### 3. Extensiones PHP
- `amqp` (para RabbitMQ)
- `gd` (para procesamiento de imágenes)
- ImageMagick (para conversión de PDFs)

## 🚀 Uso del Sistema

### 1. Iniciar Servicios
```bash
# Iniciar todos los servicios incluyendo RabbitMQ
docker-compose up -d

# Verificar que RabbitMQ esté funcionando
docker-compose exec app php scripts/start-workers.php
```

### 2. Iniciar Workers
```bash
# Iniciar workers individuales
docker-compose exec app php scripts/workers/pdf-ocr-worker.php
docker-compose exec app php scripts/workers/gemini-api-worker.php
docker-compose exec app php scripts/workers/results-worker.php

# O iniciar todos en background
docker-compose exec -d app php scripts/workers/pdf-ocr-worker.php &
docker-compose exec -d app php scripts/workers/gemini-api-worker.php &
docker-compose exec -d app php scripts/workers/results-worker.php &
```

### 3. Monitorear Colas
```bash
# Ver estadísticas de las colas
docker-compose exec app php scripts/monitor-queues.php

# Interfaz web de RabbitMQ
# http://localhost:15672
# Usuario: admin
# Contraseña: admin123
```

## 📈 Ventajas del Sistema de Colas

### 1. Rendimiento
- **Procesamiento asíncrono**: Los usuarios no esperan
- **Escalabilidad**: Múltiples workers pueden procesar en paralelo
- **Throughput**: Manejo de múltiples PDFs simultáneamente

### 2. Robustez
- **Persistencia**: Los trabajos no se pierden si el servidor se reinicia
- **Dead Letter Queues**: Manejo de trabajos que fallan
- **Retry automático**: Reintentos para trabajos fallidos

### 3. Control de Costos
- **Rate Limiting**: Control de llamadas a Gemini API
- **Circuit Breaker**: Pausa llamadas si la API está sobrecargada
- **Monitoreo**: Métricas de procesamiento y errores

### 4. Experiencia de Usuario
- **Respuesta inmediata**: Confirmación instantánea de subida
- **Progreso en tiempo real**: Seguimiento del procesamiento
- **Notificaciones**: Avisos cuando el procesamiento esté completo

## 🔧 Configuración Avanzada

### 1. Dead Letter Queues
```php
// Los mensajes que fallan van a la dead letter queue
$queueService->reject($message, false); // No requeue
```

### 2. Rate Limiting
```php
// Configurar límites de llamadas a Gemini API
'generationConfig' => [
    'temperature' => 0.1,
    'maxOutputTokens' => 4096,
]
```

### 3. Monitoreo y Logs
```php
// Logs detallados para debugging
error_log("Processing PDF OCR job: {$jobId}");
error_log("Gemini API job completed for page {$pageNumber}");
```

## 🐛 Troubleshooting

### Problemas Comunes

1. **RabbitMQ no accesible**
   ```bash
   # Verificar que RabbitMQ esté corriendo
   docker-compose ps rabbitmq
   docker-compose logs rabbitmq
   ```

2. **Workers no procesan mensajes**
   ```bash
   # Verificar que los workers estén corriendo
   docker-compose exec app php scripts/monitor-queues.php
   ```

3. **Errores de Gemini API**
   ```bash
   # Verificar la API key
   docker-compose exec app php scripts/setup-gemini-ocr.php
   ```

4. **ImageMagick no disponible**
   ```bash
   # Verificar instalación
   docker-compose exec app convert --version
   ```

### Logs y Debugging
```bash
# Ver logs de la aplicación
docker-compose logs app

# Ver logs de RabbitMQ
docker-compose logs rabbitmq

# Ver logs de workers específicos
docker-compose exec app tail -f /var/log/php_errors.log
```

## 📊 Métricas y Monitoreo

### Estadísticas de Colas
- **Mensajes en cola**: Número de trabajos pendientes
- **Consumidores activos**: Número de workers corriendo
- **Throughput**: Mensajes procesados por minuto
- **Tiempo de procesamiento**: Duración promedio por trabajo

### Alertas Recomendadas
- Cola con más de 100 mensajes pendientes
- Workers sin actividad por más de 5 minutos
- Tasa de error superior al 5%
- Tiempo de procesamiento superior a 10 minutos

## 🔮 Mejoras Futuras

### 1. Escalabilidad
- **Auto-scaling**: Ajustar número de workers automáticamente
- **Load balancing**: Distribuir carga entre múltiples instancias
- **Microservicios**: Separar workers en contenedores independientes

### 2. Funcionalidades
- **Webhooks**: Notificaciones HTTP cuando se complete el procesamiento
- **API REST**: Endpoints para consultar estado de trabajos
- **Dashboard**: Interfaz web para monitorear el sistema

### 3. Optimizaciones
- **Caching**: Cachear resultados de Gemini API
- **Compresión**: Comprimir imágenes antes de enviar a Gemini
- **Batch processing**: Procesar múltiples imágenes en una sola llamada

## 📚 Referencias

- [RabbitMQ Documentation](https://www.rabbitmq.com/documentation.html)
- [Google Gemini API](https://ai.google.dev/docs)
- [PHP AMQP Library](https://github.com/php-amqplib/php-amqplib)
- [ImageMagick](https://imagemagick.org/)

---

**Nota**: Este sistema está diseñado para manejar PDFs escaneados de manera eficiente y escalable. Para PDFs de texto, se sigue usando el procesamiento síncrono tradicional. 