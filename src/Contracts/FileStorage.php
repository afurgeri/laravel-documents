<?php

namespace Modules\Files\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorage
{
    /**
     * @return array{disk: string, path: string}
     */
    public function store(UploadedFile $file, string $disk, string $directory): array;

    /**
     * @return resource
     */
    public function readStream(string $disk, string $path): mixed;

    public function delete(string $disk, string $path): void;
}
