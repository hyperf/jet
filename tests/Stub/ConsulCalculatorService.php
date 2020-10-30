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
namespace HyperfTest\Jet\Stub;

use Hyperf\Jet\AbstractClient;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\Transporter\ConsulTransporter;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\PackerInterface;
use Hyperf\Rpc\Contract\PathGeneratorInterface;
use Hyperf\Rpc\Contract\TransporterInterface;

class ConsulCalculatorService extends AbstractClient
{
    public function __construct(
        string $service = 'ConsulCalculatorService',
        TransporterInterface $transporter = null,
        PackerInterface $packer = null,
        ?DataFormatterInterface $dataFormatter = null,
        ?PathGeneratorInterface $pathGenerator = null
    ) {
        $transporter = new ConsulTransporter('127.0.0.1', 8500, [], $service);
        $packer = new JsonEofPacker();
        parent::__construct($service, $transporter, $packer, $dataFormatter, $pathGenerator);
    }
}
