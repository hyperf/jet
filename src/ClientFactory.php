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

use Hyperf\Jet\Exception\ClientException;
use Hyperf\Jet\ProtocolManager as PM;
use Hyperf\Jet\ServiceManager as SM;
use Hyperf\Jet\Transporter\AbstractTransporter;
use Hyperf\LoadBalancer\Node;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\PackerInterface;
use Hyperf\Rpc\Contract\PathGeneratorInterface;
use Hyperf\Rpc\Contract\TransporterInterface;

class ClientFactory
{
    public function create(string $service, string $protocol): AbstractClient
    {
        [$transporter, $packer, $dataFormatter, $pathGenerator] = $this->protocolComponentGenerate($protocol);

        $this->selectNodesForTransporter($transporter, $service, $protocol);

        return new class($service, $transporter, $packer, $dataFormatter, $pathGenerator) extends AbstractClient {
        };
    }

    /**
     * @param AbstractTransporter $transporter
     * @return TransporterInterface
     */
    protected function selectNodesForTransporter(TransporterInterface $transporter, string $service, string $protocol)
    {
        // If transporter self owns load balancer , just use it.
        // else use random node from config.
        // If not exist balance load , will add transporter self host, port into the LoadBalancer.
        if ($balancer = $transporter->getLoadBalancer()) {
            if ($balanceNodes = $this->getLoadBalancerNodes($service, $protocol)) {
                $balancer->setNodes($balanceNodes);
            } else {
                $balancer->setNodes([new Node($transporter->host, $transporter->port)]);
            }

            return $transporter;
        }

        if ($randomNodes = $this->getRandomNodes($service, $protocol)) {
            [$transporter->host, $transporter->port] = $randomNodes;

            return $transporter;
        }

        return $transporter;
    }

    protected function getLoadBalancerNodes($service, $protocol): array
    {
        $nodeData = SM::getService($service, $protocol)[SM::NODES] ?? [];

        return value(function () use ($nodeData) {
            $nodes = [];
            foreach ($nodeData ?? [] as [$host, $port]) {
                $nodes[] = new Node($host, $port);
            }

            return $nodes;
        });
    }

    protected function getRandomNodes($service, $protocol)
    {
        $nodeData = SM::getService($service, $protocol)[SM::NODES] ?? [];

        if (! count($nodeData)) {
            return $nodeData;
        }

        $key = array_rand($nodeData);

        return $nodeData[$key];
    }

    protected function protocolComponentGenerate($protocol): array
    {
        $protocolMetadata = PM::getProtocol($protocol);
        $transporter = $protocolMetadata[PM::TRANSPORTER] ?? null;
        $packer = $protocolMetadata[PM::PACKER] ?? null;
        $dataFormatter = $protocolMetadata[PM::DATA_FORMATTER] ?? null;
        $pathGenerator = $protocolMetadata[PM::PATH_GENERATOR] ?? null;

        if (! $transporter instanceof TransporterInterface) {
            throw new ClientException(sprintf('The protocol of %s transporter is invalid.', $protocol));
        }

        if (! $packer instanceof PackerInterface) {
            throw new ClientException(sprintf('The protocol of %s packer is invalid.', $protocol));
        }

        if (! $dataFormatter instanceof DataFormatterInterface) {
            throw new ClientException(sprintf('The protocol of %s is data formatter invalid.', $protocol));
        }

        if (! $pathGenerator instanceof PathGeneratorInterface) {
            throw new ClientException(sprintf('The protocol of %s is path generator invalid.', $protocol));
        }

        return [$transporter, $packer, $dataFormatter, $pathGenerator];
    }
}
