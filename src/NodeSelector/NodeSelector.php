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
namespace Hyperf\Jet\NodeSelector;

use GuzzleHttp\Client;
use Hyperf\Consul\Health;
use Hyperf\Utils\Arr;

class NodeSelector
{
    public $host;

    public $port;

    public $service;

    public function __construct($host = '127.0.0.1', $port = 8500, $service = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->service = $service;
    }

    public function selectAliveNode(): array
    {
        return $this->getAliveNode();
    }

    public function selectRandomNode(): array
    {
        $nodes = $this->getAliveNode();
        if (! $nodes) {
            return [];
        }
        $key = array_rand($nodes);

        return $nodes[$key];
    }

    protected function getAliveNode(): array
    {
        $config = array_merge(['base_uri' => sprintf('http://%s:%d', $this->host, $this->port)]);
        $consulHealth = (new Health(function () use ($config) { return new Client($config); }))->service($this->service)->json();
        $balanceNodes = [];

        // Get all alive node from consul health api.
        collect($consulHealth)
            ->filter(function ($node) {
                return Arr::get($node, 'Checks.1.Status') == 'passing';
            })
            ->transform(function ($node) use (&$balanceNodes) {
                $host = Arr::get($node, 'Service.Address');
                $port = Arr::get($node, 'Service.Port');
                $balanceNodes[] = [$host, $port];
            });

        return $balanceNodes;
    }
}
