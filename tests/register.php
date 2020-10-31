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
use GuzzleHttp\Client;
use Hyperf\Consul\Agent;

require_once __DIR__ . '/../vendor/autoload.php';

$agent = new Agent(function () {
    return new Client([
        'base_uri' => 'http://127.0.0.1:8500',
        'timeout' => 2,
    ]);
});

$protocols = ['jsonrpc-http', 'jsonrpc'];
$ports = [9502, 9503];
foreach ($protocols as $i => $protocol) {
    // $agent
    $requestBody = [
        'Name' => 'CalculatorService',
        'ID' => 'CalculatorService-' . $protocol,
        'Address' => '127.0.0.1',
        'Port' => $ports[$i],
        'Meta' => [
            'Protocol' => $protocol,
        ],
    ];

    switch ($protocol) {
        case 'jsonrpc-http':
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => '90m',
                'HTTP' => "http://docker.for.mac.host.internal:{$ports[$i]}/",
                'Interval' => '1s',
            ];
            break;
        case 'jsonrpc':
        case 'jsonrpc-tcp-length-check':
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => '90m',
                'TCP' => "docker.for.mac.host.internal:{$ports[$i]}",
                'Interval' => '1s',
            ];
            break;
    }

    $agent->registerService($requestBody);
}
