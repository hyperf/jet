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

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;

class GuzzleHttpTransporter extends AbstractTransporter
{
    /**
     * @var null|Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $result;

    public function __construct(string $host = '', int $port = 9501, array $config = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->config = array_merge_recursive($config, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    public function send(string $data)
    {
        $response = $this->client()->post('/', [
            RequestOptions::BODY => $data,
        ]);

        $this->result = $response->getBody()->getContents();
    }

    public function recv()
    {
        return $this->result;
    }

    protected function client()
    {
        if (! $this->client instanceof Client) {
            if (! isset($this->config['handler'])) {
                $this->config['handler'] = HandlerStack::create();
            }
            if (! isset($this->config['base_uri'])) {
                $this->config['base_uri'] = sprintf('http://%s:%d', $this->host, $this->port);
            }

            $this->client = new Client($this->config);
        }

        return $this->client;
    }
}
