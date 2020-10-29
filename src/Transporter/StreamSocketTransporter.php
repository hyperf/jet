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
namespace Hyperf\Jet\Transporter;

use Hyperf\Jet\Exception\ClientException;
use Hyperf\Jet\Exception\ConnectionException;

class StreamSocketTransporter extends AbstractTransporter
{
    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port;

    /**
     * @var null|resource
     */
    protected $client;

    /**
     * @var float
     */
    protected $timeout;

    /**
     * @var bool
     */
    protected $isConnected = false;

    public function __construct(string $host = '', int $port = 9501, float $timeout = 1.0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function send(string $data)
    {
        $this->connect();
        fwrite($this->client, $data);
    }

    public function recv()
    {
        $buf = '';
        $timeout = 1000;

        stream_set_blocking($this->client, false);

        return retry(12, function () use (&$buf, &$timeout) {
            $read = [$this->client];
            $write = null;
            $except = null;
            while (stream_select($read, $write, $except, 0, $timeout)) {
                foreach ($read as $r) {
                    $buf .= fread($r, 8192);
                }
            }

            if (! $buf) {
                $timeout *= 2;

                throw new \Exception('no recv data');
            }

            return $buf;
        });
    }

    protected function getTarget(): array
    {
        if ($this->getLoadBalancer()) {
            $node = $this->getLoadBalancer()->select();
        } else {
            $node = $this;
        }
        if (! $node->host || ! $node->port) {
            throw new ClientException(sprintf('Invalid host %s or port %s.', $node->host, $node->port));
        }

        return [$node->host, $node->port];
    }

    protected function connect(): void
    {
        if ($this->isConnected) {
            return;
        }
        if ($this->client) {
            fclose($this->client);
            unset($this->client);
        }

        [$host, $port] = $this->getTarget();

        $client = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, $this->timeout);
        if ($client === false) {
            throw new ConnectionException(sprintf('[%d] %s', $errno, $errstr));
        }

        $this->client = $client;
        $this->isConnected = true;
    }

    protected function close(): void
    {
        if ($this->client) {
            fclose($this->client);
            $this->client = null;
        }
    }
}
