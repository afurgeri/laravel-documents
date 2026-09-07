<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Files\FileManager;
use Modules\Files\Models\StoredFile;

beforeEach(function () {
    $this->artisan('migrate');
    Storage::fake('local');
    config()->set('files.disk', 'local');
    config()->set('files.directory', 'files');
});

test('it stores a file and persists file metadata', function () {
    $file = UploadedFile::fake()->createWithContent('contract.pdf', 'contract contents');

    $storedFile = app(FileManager::class)->store($file);

    expect($storedFile)->toBeInstanceOf(StoredFile::class)
        ->and($storedFile->disk)->toBe('local')
        ->and($storedFile->original_name)->toBe('contract.pdf')
        ->and($storedFile->extension)->toBe('pdf')
        ->and($storedFile->mime_type)->toBe('application/pdf')
        ->and($storedFile->size)->toBe(17)
        ->and($storedFile->checksum)->toBe(hash('sha256', 'contract contents'))
        ->and($storedFile->path)->not->toBe('contract.pdf');

    Storage::disk('local')->assertExists($storedFile->path);
});

test('it stores a file on an explicitly selected disk', function () {
    Storage::fake('s3');

    $storedFile = app(FileManager::class)->store(
        UploadedFile::fake()->createWithContent('contract.pdf', 'contract contents'),
        disk: 's3',
    );

    expect($storedFile->disk)->toBe('s3');

    Storage::disk('s3')->assertExists($storedFile->path);
});

test('it reads a stored file as a stream', function () {
    $storedFile = app(FileManager::class)->store(
        UploadedFile::fake()->createWithContent('notes.txt', 'file contents'),
    );

    $stream = app(FileManager::class)->readStream($storedFile);

    expect(stream_get_contents($stream))->toBe('file contents');

    fclose($stream);
});

test('it replaces the stored file and removes the previous file', function () {
    $manager = app(FileManager::class);
    $storedFile = $manager->store(UploadedFile::fake()->createWithContent('old.txt', 'old contents'));
    $oldPath = $storedFile->path;

    $updated = $manager->replace(
        $storedFile,
        UploadedFile::fake()->createWithContent('new.txt', 'new contents'),
    );

    expect($updated->original_name)->toBe('new.txt')
        ->and($updated->path)->not->toBe($oldPath)
        ->and($updated->checksum)->toBe(hash('sha256', 'new contents'));

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($updated->path);
});

test('it deletes the file record and its stored file', function () {
    $manager = app(FileManager::class);
    $storedFile = $manager->store(UploadedFile::fake()->createWithContent('remove.txt', 'remove me'));
    $path = $storedFile->path;

    $manager->delete($storedFile);

    expect(StoredFile::query()->find($storedFile->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
