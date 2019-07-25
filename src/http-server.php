<?php

$http = new Swoole\Http\Server('0.0.0.0', 9501);

$http->on('request', function ($request, $response) {
    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    $client->connect('127.0.0.1', 9601, 0.5);
    // 调用connect将触发协程切换
    $client->send($request->rawContent());
    // 调用recv将触发协程切换
    $ret = $client->recv();
    $response->header('Content-Type', 'text/plain');
    $response->end('success');
    $client->close();
});

$http->start();
