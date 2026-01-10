<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Dotenv\Dotenv;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../config');
        $dotenv->load();
        parent::setUp();
    }
}
