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

### License
[MIT license](https://opensource.org/licenses/MIT).
