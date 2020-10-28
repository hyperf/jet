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
use Hyperf\Jet\Transporter\ConsulTransporter;
use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\LoadBalancer\Node;
use Hyperf\LoadBalancer\RoundRobin;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\PackerInterface;
use Hyperf\Rpc\Contract\PathGeneratorInterface;
use Hyperf\Rpc\Contract\TransporterInterface;

class ClientFactory
{
    public function create(string $service, string $protocol): AbstractClient
    {
        [$transporter, $packer, $dataFormatter, $pathGenerator] = $this->protocolGenerate($protocol);

        $transporter->setLoadBalancer($this->getLoadBalancer($service, $protocol));

        $this->adapterConsul($transporter, $service);

        return new class($service, $transporter, $packer, $dataFormatter, $pathGenerator) extends AbstractClient {
        };
    }

    protected function adapterConsul(TransporterInterface $transporter, $service)
    {
        if ($transporter instanceof ConsulTransporter) {
            $transporter->service = $service;
        }
    }

    protected function getLoadBalancer($service, $protocol): LoadBalancerInterface
    {
        $nodeData = SM::getService($service, $protocol)[SM::NODES] ?? [];

        return (new RoundRobin())->setNodes(value(function () use ($nodeData, $service, $protocol) {
            $nodes = [];
            foreach ($nodeData ?? [] as [$host, $port]) {
                $nodes[] = new Node($host, $port);
            }

            if (!count($nodes)) {
                throw new ClientException(sprintf('Service %s@%s does not register yet.', $service, $protocol));
            }

            return $nodes;
        }));
    }

    protected function protocolGenerate($protocol): array
    {
        $protocolMetadata = PM::getProtocol($protocol);
        $transporter      = $protocolMetadata[PM::TRANSPORTER] ?? null;
        $packer           = $protocolMetadata[PM::PACKER] ?? null;
        $dataFormatter    = $protocolMetadata[PM::DATA_FORMATTER] ?? null;
        $pathGenerator    = $protocolMetadata[PM::PATH_GENERATOR] ?? null;

        $this->assertProtocolTypes($transporter, $packer, $dataFormatter, $pathGenerator, $protocol);

        return [$transporter, $packer, $dataFormatter, $pathGenerator];
    }

    protected function assertProtocolTypes($transporter, $packer, $dataFormatter, $pathGenerator, $protocol)
    {
        if (!$transporter instanceof TransporterInterface) {
            throw new ClientException(sprintf('The protocol of %s transporter is invalid.', $protocol));
        }

        if (!$packer instanceof PackerInterface) {
            throw new ClientException(sprintf('The protocol of %s packer is invalid.', $protocol));
        }

        if (!$dataFormatter instanceof DataFormatterInterface) {
            throw new ClientException(sprintf('The protocol of %s is data formatter invalid.', $protocol));
        }

        if (!$pathGenerator instanceof PathGeneratorInterface) {
            throw new ClientException(sprintf('The protocol of %s is path generator invalid.', $protocol));
        }
    }

}
