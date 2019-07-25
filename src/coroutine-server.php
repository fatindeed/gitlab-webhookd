<?php

use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;
use Elasticsearch\ClientBuilder;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ElasticsearchHandler;

require_once 'vendor/autoload.php';

go(function () {
    $logLevel = ($_ENV['LOG_LEVEL'] ?: Logger::ERROR);
    $logger = new Logger('gitlab-webhook');
    if (defined('STDERR')) {
        $logger->pushHandler(new StreamHandler(STDERR, $logLevel));
    }
    if ($_ENV['ES_HOST']) {
        $client = ClientBuilder::create()
                ->setHosts([$_ENV['ES_HOST']])
                ->setRetries(0)
                ->build();
        $logger->pushHandler(new ElasticsearchHandler($client, ['index' => 'monolog-'.date('Y.m.d')], $logLevel));
        $logger->pushProcessor(function ($record) {
            $record['extra']['host'] = $_ENV['HOSTNAME'];
            return $record;
        });
    }

    $subject = new App\EventSubject($logger);
    $subject->attach(new App\Observers\DockerBuilder);

    $server = new Server('127.0.0.1', 9601);
    $server->handle(function (Connection $conn) use ($subject) {
        $data = $conn->recv();
        $subject->receive($data);
        // $conn->send("world\n");
    });
    $server->start();
});
