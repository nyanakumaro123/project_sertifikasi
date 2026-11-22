<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // View memakai @vite; tes tidak perlu file hasil build atau npm run dev
        $this->withoutVite();
    }
}