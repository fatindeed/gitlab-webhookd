<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\Service\Logger;

final class LoggerTest extends TestCase
{
    public function testLog()
    {
        // Arrange
        $ilogger = $this->createMock(LoggerInterface::class);
        // Assert
        $ilogger->expects($this->once())->method('log');
        // Act
        $logger = new Logger();
        $logger->setLogger($ilogger);
        $logger->info('foo');
    }
}
