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

namespace Hyperf\Jet\Transporter;

use GuzzleHttp\Client;
use Hyperf\Consul\Health;
use Hyperf\Jet\Exception\JetException;
use Hyperf\LoadBalancer\Node;
use Hyperf\LoadBalancer\RoundRobin;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\Utils\Arr;

class ConsulTransporter extends AbstractTransporter
{
    public const HTTP = 'http';
    public const TCP  = 'tcp';
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $result;

    protected $transporter;

    protected $timeout;

    public $service;

    public function __construct(string $host = '', int $port = 9501, array $config = [], $timeout = 1.0)
    {
        $this->host    = $host;
        $this->port    = $port;
        $this->config  = $config;
        $this->timeout = $timeout;
    }

    public function send(string $data)
    {
        $this->transporter()->send($data);
    }

    public function recv()
    {
        return $this->transporter()->recv();
    }

    protected function transporter()
    {
        if (!$this->transporter instanceof TransporterInterface) {
            $node = $this->getRoundAvailableNodeFromConsul();
            switch ($node->type) {
                case self::HTTP :
                    $this->transporter = new GuzzleHttpTransporter($node->host, $node->port, $this->config);
                    break;
                case self::TCP:
                    $this->transporter = new StreamSocketTransporter($node->host, $node->port, $this->timeout);
                    break;
                default:
                    throw new JetException(sprintf("Not found service %s in consul serve", $this->service));
            }
        }

        return $this->transporter;
    }

    protected function getRoundAvailableNodeFromConsul()
    {
        $config       = array_merge(['base_uri' => "http://{$this->host}:{$this->port}"], $this->config);
        $balanceNodes = [];
        with(
            (new Health(function () use ($config) {
                return new Client($config);
            }))
                ->service($this->service)
                ->json(),
            function ($nodes) use (&$balanceNodes) {
                return collect($nodes)
                    ->filter(function ($node) {
                        return Arr::get($node, 'Checks.1.Status') == 'passing';
                    })
                    ->transform(function ($node) use (&$balanceNodes) {
                        $host           = Arr::get($node, 'Service.Address');
                        $port           = Arr::get($node, 'Service.Port');
                        $type           = Arr::get($node, 'Checks.1.Type');
                        $weight         = 0;
                        $node           = new class($host, $port, $weight, $type) extends Node {
                            public $type;

                            public function __construct(string $host, int $port, int $weight, $type)
                            {
                                $this->type = $type;
                                parent::__construct($host, $port, $weight);
                            }
                        };
                        $balanceNodes[] = $node;
                    })
                    ->toArray();
            }
        );

        return (new RoundRobin($balanceNodes))->select();
    }
}
