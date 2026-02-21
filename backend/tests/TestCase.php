<?php

namespace Tests;

use Database\Seeders\EquipoStatusSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(EquipoStatusSeeder::class);
    }
}
