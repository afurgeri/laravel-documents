<?php

namespace Tests;

use Modules\Documents\DocumentsServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [DocumentsServiceProvider::class];
    }
}
