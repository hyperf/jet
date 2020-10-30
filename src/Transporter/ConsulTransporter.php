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
use Hyperf\LoadBalancer\Random;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\Utils\Arr;

class ConsulTransporter extends AbstractTransporter
{
    public const HTTP = 'http';

    public const TCP = 'tcp';

    public $service;

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

    public function __construct(string $host = '', int $port = 9501, array $config = ['timeout' => 1.0], $service = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->config = $config;
        $this->service = $service;
        $this->timeout = $config['timeout'] ?? 1.0;
    }

    public function send(string $data)
    {
        $this->transporterProxy()->send($data);
    }

    public function recv()
    {
        return $this->transporterProxy()->recv();
    }

    public function activeConsulService($service)
    {
        $this->service = $service;
    }

    protected function transporterProxy()
    {
        if (! $this->transporter instanceof TransporterInterface) {
            $node = $this->getRandomAvailableNodeFromConsul();

            // Get node from consul service, because random selection, therefore
            // it is instantiated transporter according to the protocol of the selected node.
            switch ($node->protocol) {
                case self::HTTP:
                    $this->transporter = new GuzzleHttpTransporter($node->host, $node->port, $this->config);
                    break;
                case self::TCP:
                    $this->transporter = new StreamSocketTransporter($node->host, $node->port, $this->timeout);
                    break;
                default:
                    throw new JetException(sprintf('Not found service %s in consul serve', $this->service));
            }
        }

        return $this->transporter;
    }

    protected function getRandomAvailableNodeFromConsul()
    {
        $consulNode = $this->getConsulNode();

        $serviceNode = $this->getAliveServiceNodeFromConsul($consulNode);

        return (new Random($serviceNode))->select();
    }

    protected function getConsulNode(): Node
    {
        if ($this->getLoadBalancer()) {
            return $this->getLoadBalancer()->select();
        }

        return new Node($this->host, $this->port);
    }

    protected function getAliveServiceNodeFromConsul(Node $consulNode)
    {
        $config = array_merge(['base_uri' => sprintf('http://%s:%d', $consulNode->host, $consulNode->port)], $this->config);
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
                $type = Arr::get($node, 'Checks.1.Type');
                $node = new class($host, $port, $type) extends Node {
                    public $protocol;

                    public function __construct(string $host, int $port, $type)
                    {
                        $this->protocol = $type;
                        parent::__construct($host, $port);
                    }
                };
                $balanceNodes[] = $node;
            });

        return $balanceNodes;
    }
}
