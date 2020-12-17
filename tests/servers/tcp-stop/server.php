<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
$server = new Swoole\Server('127.0.0.1', 10001, SWOOLE_BASE);

$server->set([
    'worker_num' => 1,
]);

// 监听连接进入事件
$server->on('Connect', function ($server, $fd) {
    echo "Client: Connect.\n";
});

// 监听数据接收事件
$server->on('Receive', function (Swoole\Server $server, $fd, $from_id, $data) {
    $server->send($fd, 'Server: ' . $data);
    $server->stop();
});

// 监听连接关闭事件
$server->on('Close', function ($server, $fd) {
    echo "Client: Close.\n";
});

// 启动服务器
$server->start();
