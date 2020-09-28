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
namespace Hyperf\Jet;

use GuzzleHttp\Client;
use Hyperf\Consul\Catalog;
use Hyperf\Jet\Exception\ClientException;
use Hyperf\Jet\ProtocolManager as PM;
use Hyperf\Jet\ServiceManager as SM;
use Hyperf\LoadBalancer\Node;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\Utils\Arr;

class ClientFactory
{
    public function create(string $service, string $protocol): AbstractClient
    {
        $serviceMetadata = SM::getService($service, $protocol);
        if (! $serviceMetadata) {
            throw new ClientException(sprintf('Service %s@%s does not register yet.', $service, $protocol));
        }
        $protocolMetadata = PM::getProtocol($protocol);
        $transporter = $protocolMetadata[PM::TRANSPORTER] ?? null;
        $packer = $protocolMetadata[PM::PACKER] ?? null;
        $dataFormatter = $protocolMetadata[PM::DATA_FORMATTER] ?? null;
        $pathGenerator = $protocolMetadata[PM::PATH_GENERATOR] ?? null;
        if (! isset($transporter, $packer, $dataFormatter, $pathGenerator)) {
            throw new ClientException(sprintf('The protocol of %s is invalid.', $protocol));
        }
        if (isset($serviceMetadata[SM::CONSUL])) {
            $nodes = with(
                (new Catalog(function () use ($serviceMetadata) {
                    return new Client(['base_uri' => $serviceMetadata[SM::CONSUL]]);
                }))
                    ->service($service)
                    ->json(),
                function ($nodes) use ($protocol) {
                    return collect($nodes)
                        ->filter(function ($node) use ($protocol) {
                            return Arr::get($node, 'ServiceMeta.Protocol') == $protocol;
                        })
                        ->transform(function ($node) {
                            return [
                                Arr::get($node, 'Address'),
                                Arr::get($node, 'ServicePort'),
                            ];
                        })
                        ->toArray();
                }
            );

            if (count($nodes)) {
                $serviceMetadata[SM::NODES] = $nodes;
            }
        }
        if (isset($serviceMetadata[SM::NODES]) && $transporter instanceof TransporterInterface) {
            if ($loadBalancer = $transporter->getLoadBalancer()) {
                $loadBalancer->setNodes(value(function () use ($serviceMetadata) {
                    $nodes = [];
                    foreach ($serviceMetadata[SM::NODES] ?? [] as [$host, $port]) {
                        $nodes[] = new Node($host, $port);
                    }
                    return $nodes;
                }));
            } elseif (count($serviceMetadata[SM::NODES]) === 1 && property_exists($transporter, 'host') && property_exists($transporter, 'port')) {
                $node = current($serviceMetadata[SM::NODES]);
                if (class_exists(Node::class) && $node instanceof Node) {
                    $host = $node->host;
                    $port = $node->port;
                } elseif (is_array($node)) {
                    [$host, $port] = $node;
                } else {
                    throw new ClientException('Invalid node info.');
                }
                $transporter->host = $host;
                $transporter->port = $port;
            }
        }
        return new class($service, $transporter, $packer, $dataFormatter, $pathGenerator) extends AbstractClient {
        };
    }
}
