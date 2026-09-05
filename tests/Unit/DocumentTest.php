<?php

use Modules\Documents\DocumentsServiceProvider;
use Modules\Documents\Models\Document;

test('it loads migrations automatically without publishing them', function () {
    expect(DocumentsServiceProvider::pathsToPublish(DocumentsServiceProvider::class, 'documents-migrations'))
        ->toBe([]);
});

test('it uses the configured document table', function () {
    config()->set('documents.table', 'stored_documents');

    expect((new Document)->getTable())->toBe('stored_documents');
});
