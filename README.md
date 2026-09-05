# Laravel Documents

Private document persistence for Laravel applications. The package stores uploaded files on any configured Laravel filesystem disk and persists the technical metadata needed to access them later.

The package does not manage business relationships, authorization, visibility, document titles, descriptions, or CRUD screens.

## Requirements

- PHP 8.3+
- Laravel 13+

## Installation

```bash
composer require afurgeri/laravel-documents
```

Publish the package configuration and migrations:

```bash
php artisan vendor:publish --tag=documents-config
php artisan vendor:publish --tag=documents-migrations
php artisan migrate
```

The package is also usable without publishing either resource. It loads its default configuration and migrations automatically.

## Configuration

The default configuration is:

```php
return [
    'disk' => env('DOCUMENTS_DISK', 'local'),
    'directory' => env('DOCUMENTS_DIRECTORY', 'documents'),
    'table' => 'documents',
];
```

Configure the default disk in `.env`:

```ini
DOCUMENTS_DISK=local
DOCUMENTS_DIRECTORY=documents
```

To use Amazon S3, configure the `s3` disk in `config/filesystems.php` and set:

```ini
DOCUMENTS_DISK=s3
```

Documents are stored using the configured disk. The disk name and generated path are persisted with each document, so existing documents continue to point to their original disk if the default changes later.

## Storing Documents

Inject `DocumentManager` into an application service or controller and pass a validated `UploadedFile`:

```php
use Illuminate\Http\UploadedFile;
use Modules\Documents\DocumentManager;

public function store(UploadedFile $file, DocumentManager $documents): Document
{
    return $documents->store($file);
}
```

The disk and directory can be overridden for an individual document:

```php
$document = $documents->store(
    file: $file,
    disk: 's3',
    directory: 'contracts',
);
```

The service generates the physical filename. The original filename is stored as metadata and is never used as the physical path.

## Reading Documents

The consuming application must authorize access before calling these methods:

```php
$stream = $documents->readStream($document);

return response()->streamDownload(
    fn () => fpassthru($stream),
    $document->original_name,
    ['Content-Type' => $document->mime_type ?? 'application/octet-stream'],
);
```

`DocumentManager` does not authorize access or expose routes. The application owns those decisions.

## Replacing Documents

```php
$document = $documents->replace($document, $newFile);
```

The new file is stored before the database reference is updated. After a successful update, the previous physical file is removed.

## Deleting Documents

```php
$documents->delete($document);
```

This removes the physical file and the `documents` record. If the same document is referenced by multiple business entities, delete it only after the application has removed all of those references.

## Persisted Metadata

| Column | Description |
| --- | --- |
| `disk` | Laravel filesystem disk used by the file |
| `path` | Generated path inside the disk |
| `original_name` | Client-provided name for presentation/downloads |
| `extension` | Extension detected from the file MIME type |
| `mime_type` | Detected MIME type |
| `size` | File size in bytes |
| `checksum` | SHA-256 checksum |

The original filename and extension are untrusted input. Validate uploads in the application before storing them, and never build storage paths from user-provided names.

## Business Relationships

The package does not know whether a document belongs to a client, contract, or another entity. The consuming application can use a direct foreign key or its own pivot table:

```php
public function documents(): BelongsToMany
{
    return $this->belongsToMany(Document::class, 'contract_documents');
}
```

Business metadata such as document type, title, visibility, retention, and permissions belongs in the consuming application.

## Testing

The package tests use `Storage::fake()` and do not require a real local or S3 service:

```bash
composer test
```

## Out of Scope

- Authorization and policies
- Public/private business visibility rules
- Business relationships and pivot tables
- CRUD or frontend components
- Automatic purging and retention policies
- Document versioning
