<?php

namespace App\Tests\Service;

use App\Service\Logger;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

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
