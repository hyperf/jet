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

use Hyperf\Jet\Exception\ServerException;
use HyperfTest\Jet\Stub\CalculatorService;
use PHPStan\Testing\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TcpRecvOverflowTest extends TestCase
{
    public function testTcpTransporterRecvOverflow()
    {
        $client   = new CalculatorService();
        $sendData = str_repeat('data', 1000000);
        try {
            $result = $client->getSendData($sendData);
        } catch (\Throwable $exception) {
            $this->expectException(ServerException::class);
            $this->expectExceptionMessage('Method not found.');
            throw $exception;
        }

        $this->assertSame($sendData, $result);
    }
}
