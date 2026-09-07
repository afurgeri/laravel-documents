# Laravel Files

Private file persistence for Laravel applications. The package stores uploaded files on any configured Laravel filesystem disk and persists the technical metadata needed to access them later.

The package does not manage business relationships, authorization, visibility, file titles, descriptions, or CRUD screens.

## Requirements

- PHP 8.3+
- Laravel 13+

## Installation

```bash
composer require afurgeri/laravel-files
```

Publish the package configuration:

```bash
php artisan vendor:publish --tag=files-config
php artisan migrate
```

The package is also usable without publishing its configuration. It loads its default configuration and migrations automatically.

## Configuration

The default configuration is:

```php
return [
    'disk' => env('FILES_DISK', 'local'),
    'directory' => env('FILES_DIRECTORY', 'files'),
    'table' => 'files',
];
```

Configure the default disk in `.env`:

```ini
FILES_DISK=local
FILES_DIRECTORY=files
```

To use Amazon S3, configure the `s3` disk in `config/filesystems.php` and set:

```ini
FILES_DISK=s3
```

Files are stored using the configured disk. The disk name and generated path are persisted with each file, so existing files continue to point to their original disk if the default changes later.

## Storing Files

Inject `FileManager` into an application service or controller and pass a validated `UploadedFile`:

```php
use Illuminate\Http\UploadedFile;
use Modules\Files\FileManager;

public function store(UploadedFile $file, FileManager $files): StoredFile
{
    return $files->store($file);
}
```

The disk and directory can be overridden for an individual file:

```php
$storedFile = $files->store(
    file: $file,
    disk: 's3',
    directory: 'contracts',
);
```

The service generates the physical filename. The original filename is stored as metadata and is never used as the physical path.

## Reading Files

The consuming application must authorize access before calling these methods:

```php
$stream = $files->readStream($storedFile);

return response()->streamDownload(
    fn () => fpassthru($stream),
    $storedFile->original_name,
    ['Content-Type' => $storedFile->mime_type ?? 'application/octet-stream'],
);
```

`FileManager` does not authorize access or expose routes. The application owns those decisions.

## Replacing Files

```php
$storedFile = $files->replace($storedFile, $newFile);
```

The new file is stored before the database reference is updated. After a successful update, the previous physical file is removed.

## Deleting Files

```php
$files->delete($storedFile);
```

This removes the physical file and the `files` record. If the same file is referenced by multiple business entities, delete it only after the application has removed all of those references.

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

The package does not know whether a file belongs to a client, contract, or another entity. The consuming application can use a direct foreign key or its own pivot table:

```php
public function files(): BelongsToMany
{
    return $this->belongsToMany(StoredFile::class, 'contract_files');
}
```

Business metadata such as file type, title, visibility, retention, and permissions belongs in the consuming application.

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
- File versioning
