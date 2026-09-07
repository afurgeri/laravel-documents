<?php

use Modules\Files\FilesServiceProvider;
use Modules\Files\Models\StoredFile;

test('it loads migrations automatically without publishing them', function () {
    expect(FilesServiceProvider::pathsToPublish(FilesServiceProvider::class, 'files-migrations'))
        ->toBe([]);
});

test('it uses the configured file table', function () {
    config()->set('files.table', 'stored_files');

    expect((new StoredFile)->getTable())->toBe('stored_files');
});
