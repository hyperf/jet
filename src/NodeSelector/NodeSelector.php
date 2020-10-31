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
    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port;

    /**
     * @var array
     */
    public $config;

    public function __construct(string $host = '127.0.0.1', int $port = 8500, array $config = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->config = $config;
    }

    public function selectAliveNodes(string $service, string $protocol): array
    {
        return $this->getAliveNodes($service, $protocol);
    }

    public function selectRandomNode(string $service, string $protocol): array
    {
        $nodes = $this->getAliveNodes($service, $protocol);
        if (! $nodes) {
            return [];
        }
        $key = array_rand($nodes);

        return $nodes[$key];
    }

    protected function getAliveNodes(string $service, string $protocol): array
    {
        $config = array_merge(['base_uri' => sprintf('http://%s:%d', $this->host, $this->port)], $this->config);
        $consulHealth = (new Health(function () use ($config) { return new Client($config); }))->service($service)->json();
        $balanceNodes = [];

        // Get all alive node from consul health api.
        collect($consulHealth)
            ->filter(function ($node) use ($protocol) {
                return Arr::get($node, 'Checks.1.Status') == 'passing'
                    && Arr::get($node, 'Service.Meta.Protocol') == $protocol;
            })
            ->transform(function ($node) use (&$balanceNodes) {
                $host = Arr::get($node, 'Service.Address');
                $port = Arr::get($node, 'Service.Port');
                $balanceNodes[] = [$host, $port];
            });

        return $balanceNodes;
    }
}
