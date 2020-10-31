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
namespace HyperfTest\Jet;

use Hyperf\Jet\DataFormatter\DataFormatter;
use Hyperf\Jet\Exception\ServerException;
use Hyperf\Jet\NodeSelector\NodeSelector;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\PathGenerator\PathGenerator;
use Hyperf\Jet\ProtocolManager;
use Hyperf\Jet\Transporter\GuzzleHttpTransporter;
use HyperfTest\Jet\Stub\ConsulCalculatorService;

/**
 * @internal
 * @coversNothing
 */
class ConsulIntegrationTest extends IntegrationTest
{
    protected $host = '127.0.0.1';

    protected $port = 8500;

    /**
     * @group consul
     */
    public function testJsonrpcCallNormalMethodWithClientFactory()
    {
        parent::testJsonrpcCallNormalMethodWithClientFactory();
    }

    /**
     * @group consul
     */
    public function testJsonrpcCallNotExistMethodWithClientFactory()
    {
        parent::testJsonrpcCallNotExistMethodWithClientFactory();
    }

    /**
     * @group consul
     */
    public function testJsonrpcCallNormalMethodWithConsul()
    {
        $client = new ConsulCalculatorService();
        $result = $client->add($a = 1, $b = 2);
        $this->assertSame($a + $b, $result);
        $result = $client->add($a = -20, $b = -10);
        $this->assertSame($a + $b, $result);
    }

    /**
     * @group consul
     */
    public function testJsonrpcCallNotExistMethodWithCustomClient()
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Method not found.');

        $client = new ConsulCalculatorService();
        $client->notExistMethod($a = 1, $b = 2);
    }

    /**
     * @group consul
     */
    public function testJsonrpcCallNormalMethodWithCustomClient()
    {
        parent::testJsonrpcCallNormalMethodWithCustomClient();
    }

    /**
     * @group consul
     */
    public function testJsonrpcCallLongData()
    {
        parent::testJsonrpcCallLongData();
    }

    protected function registerCalculatorServiceWithJsonrpcProtocol(): array
    {
        $protocol = 'jsonrpc-http';
        $service = 'CalculatorService';
        ProtocolManager::register($protocol, [
            ProtocolManager::TRANSPORTER => new GuzzleHttpTransporter(),
            ProtocolManager::PACKER => new JsonEofPacker(),
            ProtocolManager::PATH_GENERATOR => new PathGenerator(),
            ProtocolManager::DATA_FORMATTER => new DataFormatter(),
            ProtocolManager::NODE_SELECTOR => new NodeSelector($this->host, $this->port),
        ]);

        return [$service, $protocol];
    }
}
