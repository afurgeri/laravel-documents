<?php

namespace Modules\Documents\Contracts;

use Illuminate\Http\UploadedFile;

interface DocumentStorage
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
