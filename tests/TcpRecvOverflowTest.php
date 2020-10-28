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
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\PathGenerator\PathGenerator;
use Hyperf\Jet\ProtocolManager;
use Hyperf\Jet\ServiceManager;
use Hyperf\Jet\Transporter\ConsulTransporter;
use HyperfTest\Jet\Stub\CalculatorService;
use HyperfTest\Jet\Stub\ConsulCalculatorService;
use PHPStan\Testing\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TcpRecvOverflowTest extends TestCase
{
    public function testTcpTransporterRecvOverflow()
    {
        $client = new CalculatorService();
        $sendData = str_repeat('data',100000000);
        $result = $client->getSendData($sendData);

        $this->assertSame($sendData, $result);
    }
}
