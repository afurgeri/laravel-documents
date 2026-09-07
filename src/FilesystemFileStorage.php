<?php

namespace Modules\Files;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Modules\Files\Contracts\FileStorage as FileStorageContract;
use RuntimeException;

class FilesystemFileStorage implements FileStorageContract
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    /**
     * @return array{disk: string, path: string}
     */
    public function store(UploadedFile $file, string $disk, string $directory): array
    {
        $path = $this->filesystem($disk)->putFile($directory, $file);

        if ($path === false) {
            throw new RuntimeException('The file could not be stored.');
        }

        return ['disk' => $disk, 'path' => $path];
    }

    public function readStream(string $disk, string $path): mixed
    {
        $stream = $this->filesystem($disk)->readStream($path);

        if ($stream === false) {
            throw new RuntimeException('The file could not be read.');
        }

        return $stream;
    }

    public function delete(string $disk, string $path): void
    {
        if (! $this->filesystem($disk)->delete($path)) {
            throw new RuntimeException('The file could not be deleted.');
        }
    }

    private function filesystem(string $disk): FilesystemAdapter
    {
        return $this->filesystems->disk($disk);
    }
}
