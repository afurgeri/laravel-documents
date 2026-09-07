<?php

namespace Tests;

use Modules\Files\FilesServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [FilesServiceProvider::class];
    }
}
