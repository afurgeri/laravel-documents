<?php

namespace Modules\Documents;

use Illuminate\Http\UploadedFile;
use Modules\Documents\Contracts\DocumentStorage;
use Modules\Documents\Models\Document;
use RuntimeException;

class DocumentManager
{
    public function __construct(private readonly DocumentStorage $storage) {}

    public function store(UploadedFile $file, ?string $disk = null, ?string $directory = null): Document
    {
        $resolvedDisk = $disk ?? (string) config('documents.disk', 'local');
        $resolvedDirectory = $directory ?? (string) config('documents.directory', 'documents');
        $stored = $this->storage->store($file, $resolvedDisk, $resolvedDirectory);

        try {
            return Document::query()->create([
                ...$stored,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->extension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?? 0,
                'checksum' => $this->checksum($file),
            ]);
        } catch (\Throwable $exception) {
            $this->deleteStoredFile($stored['disk'], $stored['path'], $exception);

            throw $exception;
        }
    }

    public function readStream(Document $document): mixed
    {
        return $this->storage->readStream($document->disk, $document->path);
    }

    public function replace(Document $document, UploadedFile $file): Document
    {
        $previousDisk = $document->disk;
        $previousPath = $document->path;
        $stored = $this->storage->store(
            $file,
            $document->disk,
            (string) config('documents.directory', 'documents'),
        );

        try {
            $document->update([
                ...$stored,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->extension(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?? 0,
                'checksum' => $this->checksum($file),
            ]);
        } catch (\Throwable $exception) {
            $this->deleteStoredFile($stored['disk'], $stored['path'], $exception);

            throw $exception;
        }

        $this->deleteStoredFile($previousDisk, $previousPath);

        return $document->refresh();
    }

    public function delete(Document $document): void
    {
        $this->storage->delete($document->disk, $document->path);
        $document->deleteOrFail();
    }

    private function checksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $checksum = $path === false ? false : hash_file('sha256', $path);

        if ($checksum === false) {
            throw new RuntimeException('The document checksum could not be calculated.');
        }

        return $checksum;
    }

    private function deleteStoredFile(string $disk, string $path, ?\Throwable $previous = null): void
    {
        try {
            $this->storage->delete($disk, $path);
        } catch (\Throwable $cleanupException) {
            if ($previous !== null) {
                report($cleanupException);

                return;
            }

            throw $cleanupException;
        }
    }
}
