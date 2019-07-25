<?php

namespace App\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use App\EventSubject;
use App\EventObserver;

final class EventSubjectTest extends TestCase
{
    /**
     * @var \App\EventSubject
     */
    private $_subject;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    protected function setUp(): void
    {
        $this->_logger = $this->createMock(LoggerInterface::class);
        $this->_subject = new EventSubject([], $this->_logger);
    }

    public function testNotifyNever()
    {
        // Arrange
        $observer = $this->createMock(EventObserver::class);
        // Assert
        $observer->expects($this->never())->method('update');
        // Act
        $this->_subject->attach($observer);
        $this->_subject->detach($observer);
        $this->_subject->notify();
    }

    public function testGetEnvOtherEvent()
    {
        // Arrange
        $this->_subject->exchangeArray(['object_kind' => 'tag_push']);
        // Act
        $env = $this->_subject->getEnv();
        // Assert
        $this->assertEquals([], $env);
    }
}
