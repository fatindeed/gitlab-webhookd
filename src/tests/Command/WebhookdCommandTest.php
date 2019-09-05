<?php

namespace App\Tests\Command;

use App\Config;
use App\EventSubject;
use App\Service\Logger;
use App\Service\Webhookd;
use PHPUnit\Framework\TestCase;
use App\Command\WebhookdCommand;
use Symfony\Component\Console\Tester\CommandTester;

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
