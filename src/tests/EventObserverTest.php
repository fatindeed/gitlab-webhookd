<?php

namespace App\Tests;

use App\EventSubject;
use App\EventObserver;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class EventObserverTest extends TestCase
{
    public function testUpdateSuccess()
    {
        // Arrange
        $config = [
            'name' => 'success-handler',
            'type' => 'push',
            'commands' => [
                ['echo']
            ]
        ];
        $data = [
            'object_kind' => 'push',
            'ref' => 'refs/heads/master',
            'checkout_sha' => 'da1560886d4f094c3e6c9ef40349f7d38b5d27d7',
            'user_name' => 'John Smith',
            'user_email' => 'john@example.com',
            'repository' => [
                'url' => 'git@example.com:mike/diaspora.git',
                'homepage' => 'http://example.com/mike/diaspora',
            ]
        ];
        $logger = $this->createMock(LoggerInterface::class);
        // Assert
        $logger->expects($this->once())->method('notice');
        // Act
        $subject = new EventSubject($data, $logger);
        $subject->attach(new EventObserver($config));
        $subject->notify();
    }

    public function testUpdateSkipped()
    {
        // Arrange
        $config = [
            'name' => 'skipped-handler',
            'type' => 'push',
            'conditions' => [
                'repository.homepage' => 'http://example.com/foo/bar'
            ]
        ];
        $data = [
            'object_kind' => 'push',
            'ref' => 'refs/heads/master',
            'checkout_sha' => 'da1560886d4f094c3e6c9ef40349f7d38b5d27d7',
            'user_name' => 'John Smith',
            'user_email' => 'john@example.com',
            'repository' => [
                'url' => 'git@example.com:mike/diaspora.git',
                'homepage' => 'http://example.com/mike/diaspora',
            ]
        ];
        $logger = $this->createMock(LoggerInterface::class);
        // Assert
        $logger->expects($this->once())->method('debug');
        // Act
        $subject = new EventSubject($data, $logger);
        $subject->attach(new EventObserver($config));
        $subject->notify();
    }

    public function testValidateExpectArray()
    {
        // Act
        $return = EventObserver::validate(
            'refs/heads/develop',
            [
                'refs/heads/master',
                'refs/heads/develop'
            ]
        );
        // Assert
        $this->assertTrue($return);
    }

    public function testValidateExpectWildcard()
    {
        // Act
        $return = EventObserver::validate('refs/heads/release/new', 'refs/heads/release/*');
        // Assert
        $this->assertTrue($return);
    }
}
