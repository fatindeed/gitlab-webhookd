<?php

namespace App\Service;

use Swoole\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Psr\Log\LoggerInterface;
use App\EventSubject;

/**
 * Webhookd class
 *
 * @see https://wiki.swoole.com/wiki/page/326.html
 */
class Webhookd
{
    /**
     * Server instance
     *
     * @var \Swoole\Http\Server
     */
    private $_server;

    /**
     * Event subject instance
     *
     * @var \App\EventSubject
     */
    private $_subject;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Construct a new webhookd service.
     *
     * @param \App\EventSubject        $subject Event subject instance
     * @param \Psr\Log\LoggerInterface $logger  Logger instance
     */
    public function __construct(EventSubject $subject, LoggerInterface $logger)
    {
        $this->_subject = $subject;
        $this->_logger = $logger;
    }

    /**
     * Sets a http server.
     *
     * @param \Swoole\Http\Server $server Server instance
     *
     * @return void
     */
    public function setServer(Server $server): void
    {
        $this->_server = $server;
        $this->_server->set(
            [
            'worker_num' => 1,
            'task_worker_num' => 1,
            // 'task_ipc_mode' => 3,
            // 'message_queue_key' => 0x70001001,
            // 'task_tmpdir' => '/data/task/',
            ]
        );
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if ($method != 'on' && substr($method, 0, 2) == 'on') {
                $this->_server->on(substr($method, 2), [$this, $method]);
                $this->_logger->debug(
                    '{event} event attached.',
                    [
                    'event' => $method
                    ]
                );
            }
        }
    }

    /**
     * Start the http server.
     *
     * @return void
     */
    public function start(): void
    {
        $this->_server->start();
    }

    /**
     * On start event.
     *
     * @param \Swoole\Server $server Server instance
     *
     * @return void
     *
     * @see https://wiki.swoole.com/wiki/page/p-event/onStart.html
     */
    public function onStart(Server $server): void
    {
        $port = $server->ports[0];
        $this->_logger->warning(
            'Http server started, listening on http://{host}:{port}',
            [
            'host' => $port->host,
            'port' => $port->port
            ]
        );
    }

    /**
     * On shutdown event.
     *
     * @param \Swoole\Server $server Server instance
     *
     * @return void
     *
     * @see https://wiki.swoole.com/wiki/page/p-event/onShutdown.html
     */
    public function onShutdown(Server $server): void
    {
        $this->_logger->warning('Http server stopped.');
    }

    /**
     * On worker start event.
     *
     * @param \Swoole\Server $server   Server instance
     * @param int            $workerId Worker id
     *
     * @return void
     *
     * @see https://wiki.swoole.com/wiki/page/p-event/onWorkerStart.html
     */
    public function onWorkerStart(Server $server, int $workerId): void
    {
        global $argv;
        swoole_set_process_name('php ' . $argv[0] . ': ' . ($server->taskworker ? 'task_worker' : 'worker'));
        $this->_logger->info(
            '{worker}(id:{id}) started.',
            [
            'worker' => ($server->taskworker ? 'Task worker' : 'Worker'),
            'id' => $workerId
            ]
        );
    }

    /**
     * On worker stop event.
     *
     * @param \Swoole\Server $server   Server instance
     * @param int            $workerId Worker id
     *
     * @return void
     *
     * @see https://wiki.swoole.com/wiki/page/p-event/onWorkerStop.html
     */
    public function onWorkerStop(Server $server, int $workerId): void
    {
        $this->_logger->info('Worker(id:{id}) stopped.', ['id' => $workerId]);
    }

    /**
     * On request event.
     *
     * @param \Swoole\Http\Request  $request  Request instance
     * @param \Swoole\Http\Response $response Response instance
     *
     * @return void
     *
     * @see https://wiki.swoole.com/wiki/page/330.html
     */
    public function onRequest(Request $request, Response $response): void
    {
        // $request->header['x-gitlab-event']
        // $request->header['x-gitlab-token']
        $data = $request->rawContent();
        $this->_logger->debug('New request received: {data}', ['data' => $request->getData()]);
        $this->_server->task($data);
        $response->header('Content-Type', 'text/plain');
        $response->end('success');
    }

    /**
     * On task event
     *
     * @param \Swoole\Server $server      Server instance
     * @param int            $taskId      Task id
     * @param int            $srcWorkerId Worker id
     * @param string         $data        Task input value
     *
     * @return int
     *
     * @see https://wiki.swoole.com/wiki/page/54.html
     */
    public function onTask(Server $server, int $taskId, int $srcWorkerId, string $data): int
    {
        $this->_logger->info(
            'Task(id:{id},worker_id:{worker_id}) started.',
            [
                'id' => $taskId,
                'worker_id' => $srcWorkerId
            ]
        );
        $input = json_decode($data, true);
        $return = json_last_error();
        if ($return == JSON_ERROR_NONE) {
            $this->_subject->exchangeArray($input);
            $this->_subject->notify();
        } else {
            $this->_logger->error(
                'Decoding: {data} - {message}',
                [
                'data' => var_export($data, true),
                'message' => json_last_error_msg()
                ]
            );
        }
        return $return;
    }

    /**
     * On task finish event
     *
     * @param \Swoole\Server $server Server instance
     * @param int            $taskId Task id
     * @param mixed          $data   Task return value
     *
     * @return void
     *
     * @see https://wiki.swoole.com/wiki/page/136.html
     */
    public function onFinish(Server $server, int $taskId, $data): void
    {
        $this->_logger->info('Task(id:{id}) finished.', ['id' => $taskId]);
    }
}
