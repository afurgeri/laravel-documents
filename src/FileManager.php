<?php

namespace Modules\Files;

use Illuminate\Http\UploadedFile;
use Modules\Files\Contracts\FileStorage;
use Modules\Files\Models\StoredFile;
use RuntimeException;

class FileManager
{
    public function __construct(private readonly FileStorage $storage) {}

    public function store(UploadedFile $file, ?string $disk = null, ?string $directory = null): StoredFile
    {
        $resolvedDisk = $disk ?? (string) config('files.disk', 'local');
        $resolvedDirectory = $directory ?? (string) config('files.directory', 'files');
        $stored = $this->storage->store($file, $resolvedDisk, $resolvedDirectory);

        try {
            return StoredFile::query()->create([
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

    public function readStream(StoredFile $file): mixed
    {
        return $this->storage->readStream($file->disk, $file->path);
    }

    public function replace(StoredFile $storedFile, UploadedFile $file): StoredFile
    {
        $previousDisk = $storedFile->disk;
        $previousPath = $storedFile->path;
        $stored = $this->storage->store(
            $file,
            $storedFile->disk,
            (string) config('files.directory', 'files'),
        );

        try {
            $storedFile->update([
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

        return $storedFile->refresh();
    }

    public function delete(StoredFile $file): void
    {
        $this->storage->delete($file->disk, $file->path);
        $file->deleteOrFail();
    }

    private function checksum(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        $checksum = $path === false ? false : hash_file('sha256', $path);

        if ($checksum === false) {
            throw new RuntimeException('The file checksum could not be calculated.');
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
