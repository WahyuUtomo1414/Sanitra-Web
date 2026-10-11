<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');

        if ($connection !== 'sqlite') {
            $database = config("database.connections.{$connection}.database");

            throw new RuntimeException(
                "Testing database must be sqlite in-memory, got connection '{$connection}' "
                ."(database '{$database}'). Hapus bootstrap/cache/config.php dan jalankan "
                .'`php artisan config:clear`. Lihat docs/testing-safety.md.'
            );
        }
    }
}
