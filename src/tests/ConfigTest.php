<?php

namespace App\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use App\Config;

final class ConfigTest extends TestCase
{
    public function testInvalidPort()
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1001);
        // Act
        $config = new Config(['port' => -1]);
    }
}
