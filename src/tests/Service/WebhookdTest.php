<?php

namespace App\Tests\Service;

use Swoole\Server;
use ReflectionClass;
use App\EventSubject;
use Swoole\Http\Request;
use Swoole\Http\Response;
use App\Service\Webhookd;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

final class WebhookdTest extends TestCase
{
    /**
     * @var \App\Service\Webhookd
     */
    private $_webhookd;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $_logger;

    protected function setUp(): void
    {
        $this->_logger = $this->createMock(LoggerInterface::class);
        $subject = new EventSubject([], $this->_logger);
        $this->_webhookd = new Webhookd($subject, $this->_logger);
    }

    public function testStart()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $server->expects($this->once())->method('start');
        // Act
        $this->_webhookd->setServer($server);
        $this->_webhookd->start();
    }

    public function testOnStart()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        $reflectionServer = new ReflectionClass($server);
        $property = $reflectionServer->getProperty('ports');
        $property->setValue(
            $server,
            [(object) ['host' => '0.0.0.0','port' => 9501]]
        );
        // Assert
        $this->_logger->expects($this->once())->method('warning');
        // Act
        $this->_webhookd->onStart($server);
    }

    public function testOnShutdown()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $this->_logger->expects($this->once())->method('warning');
        // Act
        $this->_webhookd->onShutdown($server);
    }

    public function testOnWorkerStart()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $this->_logger->expects($this->once())->method('info');
        // Act
        $this->_webhookd->onWorkerStart($server, 0);
    }

    public function testOnWorkerStop()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $this->_logger->expects($this->once())->method('info');
        // Act
        $this->_webhookd->onWorkerStop($server, 0);
    }

    public function testOnRequest()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        // Assert
        $request->expects($this->once())->method('rawContent')->willReturn('foo');
        $request->expects($this->once())->method('getData')->willReturn('bar');
        $this->_logger->expects($this->atLeastOnce())->method('debug');
        $server->expects($this->once())->method('task');
        $response->expects($this->once())->method('header');
        $response->expects($this->once())->method('end');
        // Act
        $this->_webhookd->setServer($server);
        $this->_webhookd->onRequest($request, $response);
    }

    public function testOnTaskSuccess()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $this->_logger->expects($this->once())->method('info');
        // Act
        $return = $this->_webhookd->onTask($server, 0, 0, '{"foo":"bar"}');
        // Assert
        $this->assertEquals(0, $return);
    }

    public function testOnTaskFailed()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $this->_logger->expects($this->once())->method('info');
        $this->_logger->expects($this->once())->method('error');
        // Act
        $return = $this->_webhookd->onTask($server, 0, 0, '"foo":"bar"');
        // Assert
        $this->assertEquals(4, $return);
    }

    public function testOnFinish()
    {
        // Arrange
        $server = $this->createMock(Server::class);
        // Assert
        $this->_logger->expects($this->once())->method('info');
        // Act
        $this->_webhookd->onFinish($server, 0, 0);
    }
}
