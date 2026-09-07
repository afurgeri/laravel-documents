<?php

namespace Modules\Files;

use Illuminate\Support\ServiceProvider;
use Modules\Files\Contracts\FileStorage as FileStorageContract;

class FilesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/files.php', 'files');

        $this->app->bind(FileStorageContract::class, FilesystemFileStorage::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/files.php' => config_path('files.php'),
        ], 'files-config');
    }
}
