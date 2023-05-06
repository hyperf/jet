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

use Hyperf\Stringable\Str;
use Throwable;

/**
 * Retry.
 * @return mixed
 * @throws Throwable
 * @deprecated since v0.6.0, use `Hyperf\Support\retry()` instead.
 */
function retry(int $times, callable $callback, int $sleep = 0, callable $when = null)
{
    $attempts = 0;

    beginning:
    $attempts++;
    --$times;

    try {
        return $callback($attempts);
    } catch (Throwable $e) {
        if ($times < 1 || ($when && ! $when($e))) {
            throw $e;
        }

        if ($sleep) {
            usleep($sleep * 1000);
        }

        goto beginning;
    }
}

/**
 * @return string
 * @deprecated since v0.6.0, use `Hyperf\Stringable\Str::lower()` instead.
 */
function str_lower(string $value)
{
    return mb_strtolower($value, 'UTF-8');
}

/**
 * @param string $delimiter
 * @return string
 * @deprecated since v0.6.0, use `Hyperf\Stringable\Str::snake()` instead.
 */
function str_snake(string $value, $delimiter = '_')
{
    if (! ctype_lower($value)) {
        $value = preg_replace('/\s+/u', '', ucwords($value));
        $value = Str::lower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
    }

    return $value;
}

/**
 * Replace the last occurrence of a given value in the string.
 * @deprecated since v0.6.0, use `Hyperf\Stringable\Str::replaceLast()` instead.
 */
function str_replace_last(string $search, string $replace, string $subject): string
{
    $position = strrpos($subject, $search);

    if ($position !== false) {
        return substr_replace($subject, $replace, $position, strlen($search));
    }

    return $subject;
}
