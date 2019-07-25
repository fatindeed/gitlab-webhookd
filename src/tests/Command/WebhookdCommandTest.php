<?php

namespace App\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;
use App\Command\WebhookdCommand;
use App\Config;
use App\EventSubject;
use App\Service\Webhookd;
use App\Service\Logger;

final class WebhookdCommandTest extends TestCase
{
    public function testExecute()
    {
        // Arrange
        $src = realpath(__DIR__ . '/../../');
        chdir($src);
        $webhookd = $this->createMock(Webhookd::class);
        // Assert
        $webhookd->expects($this->once())->method('setServer');
        $webhookd->expects($this->once())->method('start');
        // Act
        $logger = new Logger();
        $subject = new EventSubject([], $logger);
        $command = new WebhookdCommand($webhookd, $subject, $logger);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
    }
}
