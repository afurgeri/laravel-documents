<?php

use Modules\Documents\Models\Document;

test('it uses the configured document table', function () {
    config()->set('documents.table', 'stored_documents');

    expect((new Document)->getTable())->toBe('stored_documents');
});
