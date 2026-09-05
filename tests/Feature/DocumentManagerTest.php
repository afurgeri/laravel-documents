<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Documents\DocumentManager;
use Modules\Documents\Models\Document;

beforeEach(function () {
    $this->artisan('migrate');
    Storage::fake('local');
    config()->set('documents.disk', 'local');
    config()->set('documents.directory', 'documents');
});

test('it stores a document and persists file metadata', function () {
    $file = UploadedFile::fake()->createWithContent('contract.pdf', 'contract contents');

    $document = app(DocumentManager::class)->store($file);

    expect($document)->toBeInstanceOf(Document::class)
        ->and($document->disk)->toBe('local')
        ->and($document->original_name)->toBe('contract.pdf')
        ->and($document->extension)->toBe('pdf')
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->size)->toBe(17)
        ->and($document->checksum)->toBe(hash('sha256', 'contract contents'))
        ->and($document->path)->not->toBe('contract.pdf');

    Storage::disk('local')->assertExists($document->path);
});

test('it stores a document on an explicitly selected disk', function () {
    Storage::fake('s3');

    $document = app(DocumentManager::class)->store(
        UploadedFile::fake()->createWithContent('contract.pdf', 'contract contents'),
        disk: 's3',
    );

    expect($document->disk)->toBe('s3');

    Storage::disk('s3')->assertExists($document->path);
});

test('it reads a stored document as a stream', function () {
    $document = app(DocumentManager::class)->store(
        UploadedFile::fake()->createWithContent('notes.txt', 'document contents'),
    );

    $stream = app(DocumentManager::class)->readStream($document);

    expect(stream_get_contents($stream))->toBe('document contents');

    fclose($stream);
});

test('it replaces the stored file and removes the previous file', function () {
    $manager = app(DocumentManager::class);
    $document = $manager->store(UploadedFile::fake()->createWithContent('old.txt', 'old contents'));
    $oldPath = $document->path;

    $updated = $manager->replace(
        $document,
        UploadedFile::fake()->createWithContent('new.txt', 'new contents'),
    );

    expect($updated->original_name)->toBe('new.txt')
        ->and($updated->path)->not->toBe($oldPath)
        ->and($updated->checksum)->toBe(hash('sha256', 'new contents'));

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($updated->path);
});

test('it deletes the document record and its stored file', function () {
    $manager = app(DocumentManager::class);
    $document = $manager->store(UploadedFile::fake()->createWithContent('remove.txt', 'remove me'));
    $path = $document->path;

    $manager->delete($document);

    expect(Document::query()->find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
