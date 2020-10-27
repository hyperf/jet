<?php

// declare(strict_types=1);

namespace HyperfTest\Jet;

use Hyperf\Jet\ClientFactory;
use Hyperf\Jet\DataFormatter\DataFormatter;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\PathGenerator\PathGenerator;
use Hyperf\Jet\ProtocolManager;
use Hyperf\Jet\ServiceManager;
use Hyperf\Jet\Transporter\ConsulTransporter;

require_once('../vendor/autoload.php');

/**
 * @internal
 * @coversNothing
 */
class ConsulIntegrationTest
{

    protected $host = '127.0.0.1';

    protected $port = 8500;

    public function test()
    {
        $protocol = 'consul';
        ProtocolManager::register($protocol, [
            ProtocolManager::TRANSPORTER => new ConsulTransporter(),
            ProtocolManager::PACKER => new JsonEofPacker(),
            ProtocolManager::PATH_GENERATOR => new PathGenerator(),
            ProtocolManager::DATA_FORMATTER => new DataFormatter(),
        ]);
        $service = 'Hk8591GoodsSearchService';
        ServiceManager::register($service, $protocol, [
            ServiceManager::NODES => [
                [$this->host, $this->port],
            ],
        ]);

        $clientFactory = new ClientFactory();
        $client = $clientFactory->create($service, $protocol = 'consul');

        return $client;
    }
}

$test = new ConsulIntegrationTest();
$test->test()->search([]);
