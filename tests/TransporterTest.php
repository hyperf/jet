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

use Hyperf\Jet\Exception\ConnectionException;
use Hyperf\Jet\Transporter\StreamSocketTransporter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class TransporterTest extends TestCase
{
    public function testStreamSocketWhenServerStoped()
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection was closed.');
        $transporter = new StreamSocketTransporter('127.0.0.1', 10001);
        $transporter->send('xxx');
        $transporter->recv();
        $transporter->send('xxx');
        $transporter->recv();
    }
}
