API Laravel EBilling
=

Laravel library to manage common operations used in electronic billing processes. <br>
It includes:
- <b>Document request manager:</b> manage document requests and retrieve them by document ID.


### Install

Run the following commands to install: <br>
```
composer require experteam/api-laravel-ebilling
```

### Update
Run the composer command to update the package: <br>
```
composer update experteam/api-laravel-ebilling
```

### Use
After installing the package, you should run the migration command to create the necessary tables in your database. <br>
```
php artisan migrate
```
this will create the following tables:
- document_requests

#### Document Request Manager

You can use the Document Request Manager to store and retrieve document requests:

```php
// Store a document request
$documentRequest = DocumentRequestManager::store($documentId, $service, $observation);

// Get document requests by document ID
$documentRequests = DocumentRequestManager::getByDocumentId($documentId);
```

#### API Endpoints

The package provides the following API endpoints:

- `GET /api/document-requests/{documentId}`: Get all document requests for a specific document ID.

Example response:
```json
{
  "success": true,
  "data": {
    "requests": [
      {
        "id": 1,
        "document_id": 123,
        "service": "example_service",
        "times": [
          "2025-07-23T18:57:00.000000Z"
        ],
        "observation": "Example observation",
        "created_at": "2025-07-23T18:57:00.000000Z",
        "updated_at": "2025-07-23T18:57:00.000000Z"
      }
    ]
  }
}
```

# Saga para `experteam/api-laravel-ebilling`

Este módulo agrega al API un consumidor de solicitudes y un publicador de resultados usando Redis Streams. **Cada instancia de API B se configura con una única cola de solicitudes correspondiente a su país.** No se configura ni valida el país en el código; `country_id` se recibe y almacena como dato del evento.

## 1. Contratos de mensajes

### Solicitud recibida desde API Invoices

```json
{
  "event_id": "uuid",
  "saga_id": "uuid",
  "event_type": "operation.requested",
  "operation": "ISSUE_INVOICE",
  "event_version": 1,
  "country_id": 146,
  "correlation_id": "uuid",
  "occurred_at": "2026-11-20T10:00:10Z",
  "payload": {}
}
```

`operation` admite `ISSUE_INVOICE` o `CANCEL_DOCUMENT`.

### Respuesta enviada a API Invoices

Para una ejecución correcta, `event_type` será `operation.completed`; para un error, `operation.failed`.

```json
{
  "event_id": "uuid-generado-por-api-b",
  "saga_id": "uuid-de-la-solicitud",
  "event_type": "operation.completed",
  "event_version": 1,
  "correlation_id": "uuid-de-la-solicitud",
  "occurred_at": "2026-11-20T10:00:10Z",
  "payload": {}
}
```

## 2. Configuración por instancia de país

Ejemplo para la instancia de Ecuador:

```dotenv
EBILLING_REDIS_CONNECTION=events
EBILLING_REQUESTS_STREAM=billing.fiscal.ec.requested
EBILLING_RESULTS_STREAM=billing.fiscal.results
EBILLING_CONSUMER_GROUP=ebilling-workers
EBILLING_FISCAL_HANDLER="App\\Services\\CountryFiscalOperationHandler"
```

En la instancia de Colombia solo cambia `EBILLING_REQUESTS_STREAM` por `billing.fiscal.co.requested`. Cada API B escucha únicamente el stream configurado para ella. Todas publican los resultados en el stream común configurado en `EBILLING_RESULTS_STREAM`.

La conexión `events` debe existir en `config/database.php`. El adaptador soporta PhpRedis y Predis. Redis 6.2 o superior es necesario por el uso de `XAUTOCLAIM` para recuperar mensajes pendientes. Si utilizas Predis con prefijo global de claves, configura `EBILLING_REDIS_STREAM_PREFIX` con ese mismo prefijo.

## 3. Implementación fiscal del país

Cada aplicación registra una clase que implemente el contrato del paquete:

```php
<?php

namespace App\Services;

use Experteam\ApiLaravelEBilling\Saga\Contracts\FiscalOperationHandlerInterface;

class CountryFiscalOperationHandler implements FiscalOperationHandlerInterface
{
    public function __construct(
        private InvoiceService $invoiceService,
        private CancellationService $cancellationService,
    ) {}

    public function issueInvoice(array $payload, string $sagaId): array
    {
        // Conectar con la firma real del servicio existente.
        return $this->invoiceService->issue($payload, $sagaId);
    }

    public function cancelDocument(array $payload, string $sagaId): array
    {
        // El servicio del país decide anulación directa o nota de crédito.
        return $this->cancellationService->cancel($payload, $sagaId);
    }
}
```

Los nombres `issue` y `cancel` son ejemplos: adapta esas llamadas a las firmas reales del proyecto. Los servicios fiscales deberían utilizar `sagaId` como referencia idempotente para evitar ejecutar dos veces una operación si Redis entrega nuevamente el mismo mensaje. En particular, esto protege frente a un reinicio ocurrido después de que el proveedor fiscal procesó la solicitud pero antes de que API B guardara el resultado.

Para un rechazo fiscal/de negocio definitivo, el servicio debe lanzar `FiscalOperationRejectedException`; API B publica `operation.failed` con `payload.error_code` y `payload.message`. Los errores técnicos no se convierten en `operation.failed`: el mensaje queda pendiente para que Redis Streams lo vuelva a entregar. Los servicios fiscales deben ser idempotentes por `sagaId`, para que una repetición no genere dos documentos.

## 4. Instalación y ejecución

```bash
composer dump-autoload
php artisan vendor:publish --tag=ebilling-saga-config
php artisan migrate
```

Ejecutar el consumidor como proceso permanente:

```bash
php artisan ebilling:saga:consume
```

Ejecutar el publicador del Outbox mediante Scheduler:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ebilling:saga:publish-outbox')
    ->everyMinute()
    ->withoutOverlapping();
```

El consumidor guarda el resultado en el Outbox antes de confirmar el mensaje de entrada. El publicador envía ese resultado al stream común. Si Redis acepta un resultado y el proceso se interrumpe antes de marcarlo como publicado, puede existir una entrega duplicada; API A debe deduplicar respuestas por `event_id`.

### License
[MIT license](https://opensource.org/licenses/MIT).

