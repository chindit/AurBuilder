<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

abstract class AbstractProphetTest extends TestCase
{
    protected Prophet $prophet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prophet = new Prophet();
    }

    protected function tearDown(): void
    {
        $this->prophet->checkPredictions();

        parent::tearDown();
    }
}
