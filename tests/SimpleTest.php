<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testBasicAssertion(): void
    {
        $this->assertTrue(true);
    }

    public function testStringComparison(): void
    {
        $this->assertEquals('hello', 'hello');
    }
} 