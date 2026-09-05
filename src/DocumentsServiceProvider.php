<?php

namespace Modules\Documents;

use Illuminate\Support\ServiceProvider;
use Modules\Documents\Contracts\DocumentStorage as DocumentStorageContract;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/documents.php', 'documents');

        $this->app->bind(DocumentStorageContract::class, FilesystemDocumentStorage::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/documents.php' => config_path('documents.php'),
        ], 'documents-config');

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'documents-migrations');
    }
}
