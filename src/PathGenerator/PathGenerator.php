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
namespace Hyperf\Jet\PathGenerator;

use function Hyperf\Jet\str_replace_last;
use function Hyperf\Jet\str_snake;

class PathGenerator implements \Hyperf\Rpc\Contract\PathGeneratorInterface
{
    public function generate(string $service, string $method): string
    {
        $handledNamespace = explode('\\', $service);
        $handledNamespace = str_replace_last('Service', '', end($handledNamespace));
        $path = str_snake($handledNamespace);

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        return $path . '/' . $method;
    }
}
