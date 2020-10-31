English | [中文](./README-CN.md)

<p align="center"><a href="https://hyperf.io" target="_blank" rel="noopener noreferrer"><img width="70" src="https://cdn.jsdelivr.net/gh/hyperf/hyperf/docs/logo.png" alt="Hyperf Logo"></a></p>

<p align="center">
  <a href="https://github.com/hyperf/jet/releases"><img src="https://poser.pugx.org/hyperf/jet/v/stable" alt="Stable Version"></a>
  <a href="https://travis-ci.org/hyperf/jet"><img src="https://travis-ci.org/hyperf/jet.svg?branch=master" alt="Build Status"></a>
  <a href="https://packagist.org/packages/hyperf/jet"><img src="https://poser.pugx.org/hyperf/jet/downloads" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/hyperf/jet"><img src="https://poser.pugx.org/hyperf/jet/d/monthly" alt="Monthly Downloads"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/php-%3E=7.2-brightgreen.svg?maxAge=2592000" alt="Php Version"></a>
  <a href="https://github.com/hyperf/jet/blob/master/LICENSE"><img src="https://img.shields.io/github/license/hyperf/jet.svg?maxAge=2592000" alt="Hyperf Jet License"></a>
</p>

# Jet, by Hyperf

Jet is a unification model RPC Client, built-in JSONRPC protocol, available to running in ALL PHP environments, including PHP-FPM and Swoole/Hyperf environments. 

> Also will built-in gRPC and Tars protocols in future.

# Installation

```bash
composer require hyperf/jet
```

# Quickstart

## Register protocol

> Register the protocol is not necessary, but you could manage the protocols more easily by using ProtocolManager.

You cloud register any protocol by `Hyperf\Jet\ProtocolManager`, per protocol basically including Transporter, Packer, DataFormatter and PathGenerator, you could register a JSONRPC protocol like below: 

```php
<?php

use Hyperf\Jet\DataFormatter\DataFormatter;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\PathGenerator\PathGenerator;
use Hyperf\Jet\ProtocolManager;
use Hyperf\Jet\Transporter\StreamSocketTransporter;

ProtocolManager::register($protocol = 'jsonrpc', [
    ProtocolManager::TRANSPORTER => new StreamSocketTransporter(),
    ProtocolManager::PACKER => new JsonEofPacker(),
    ProtocolManager::PATH_GENERATOR => new PathGenerator(),
    ProtocolManager::DATA_FORMATTER => new DataFormatter(),
]);
```
If you use consul, you could register a JSONRPC protocol like below:
```php
<?php

use Hyperf\Jet\DataFormatter\DataFormatter;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\PathGenerator\PathGenerator;
use Hyperf\Jet\ProtocolManager;
use Hyperf\Jet\Transporter\ConsulTransporter;

// If you use consul service, you should register  ProtocolManager::NODE_SELECTOR.

ProtocolManager::register($protocol = 'consul', [
    ProtocolManager::TRANSPORTER => new StreamSocketTransporter(),
    ProtocolManager::PACKER => new JsonEofPacker(),
    ProtocolManager::PATH_GENERATOR => new PathGenerator(),
    ProtocolManager::DATA_FORMATTER => new DataFormatter(),
    ProtocolManager::NODE_SELECTOR => new NodeSelector($this->host, $this->port, $config), 
]);

```

## Register service

> > Register the service is not necessary, but you could manage the services more easily by using ServiceManager.

After you registered a protocol to `Hyperf\Jet\ProtocolManager`, you could bind the protocol with any services by `Hyperf\Jet\ServiceManager`, like below:

```php
<?php
use Hyperf\Jet\ServiceManager;

// Bind CalculatorService with jsonrpc protocol, and set the static nodes info.
ServiceManager::register($service = 'CalculatorService', $protocol = 'jsonrpc', [
    ServiceManager::NODES => [
        [$host = '127.0.0.1', $port = 9503],
    ],
]);
```

## Call RPC method

### Call by ClientFactory

After you registered the protocol and service, you could get your service client via `Hyperf/Jet/ClientFactory`, like below:

```php
<?php
use Hyperf\Jet\ClientFactory;

$clientFactory = new ClientFactory();
$client = $clientFactory->create($service = 'CalculatorService', $protocol = 'jsonrpc');
```

When you have the client object, you could call any remote methods via the object, like below: 

```php
// Call the remote method `add` with arguments `1` and `2`.
// The $result is the result of the remote method.
$result = $client->add(1, 2);
```

If you call a not exist remote method, the client will throw an `Hyperf\Jet\Exception\ServerException` exception.

### Call by custom client

You could also create a custom client class which extends `Hyperf\Jet\AbstractClient`, to call the remote methods via the client object.   
For example, you want to define a RPC client for `CalculatorService` with `jsonrpc` protocol, you could create a `CalculatorService` class firstly, like below:

```php
<?php

use Hyperf\Jet\AbstractClient;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\Transporter\StreamSocketTransporter;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\PackerInterface;
use Hyperf\Rpc\Contract\PathGeneratorInterface;
use Hyperf\Rpc\Contract\TransporterInterface;

/**
 * @method int add(int $a, int $b);
 */
class CalculatorService extends AbstractClient
{
    // Define `CalculatorService` as the default value of $service.
    public function __construct(
        string $service = 'CalculatorService',
        TransporterInterface $transporter = null,
        PackerInterface $packer = null,
        ?DataFormatterInterface $dataFormatter = null,
        ?PathGeneratorInterface $pathGenerator = null
    ) {
        // Specific the transporter here, you could also retrieve the transporter from ProtocolManager or passing by constructor.
        $transporter = new StreamSocketTransporter('127.0.0.1', 9503);
        // Specific the packer here, you could also retrieve the packer from ProtocolManager or passing by constructor.
        $packer = new JsonEofPacker();
        parent::__construct($service, $transporter, $packer, $dataFormatter, $pathGenerator);
    }
}
```
If use Consul service, you can use it in the following way.
```php
<?php

use Hyperf\Jet\AbstractClient;
use Hyperf\Jet\Packer\JsonEofPacker;
use Hyperf\Jet\Transporter\StreamSocketTransporter;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\PackerInterface;
use Hyperf\Rpc\Contract\PathGeneratorInterface;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\Jet\NodeSelector\NodeSelector;

/**
 * @method int add(int $a, int $b);
 */
class CalculatorService extends AbstractClient
{
    // Define `CalculatorService` as the default value of $service.
    public function __construct(
        string $service = 'CalculatorService',
        TransporterInterface $transporter = null,
        PackerInterface $packer = null,
        ?DataFormatterInterface $dataFormatter = null,
        ?PathGeneratorInterface $pathGenerator = null
    ) {
        // Specific the transporter here, you could also retrieve the transporter from ProtocolManager or passing by constructor.
        $transporter = new StreamSocketTransporter();
        $nodeSelector = new NodeSelector('127.0.0.1', 8500, $config);
        [$transporter->host, $transporter->port] = $nodeSelector->selectRandomNode($service, 'jsonrpc');
        // Specific the packer here, you could also retrieve the packer from ProtocolManager or passing by constructor.
        $packer = new JsonEofPacker();
        parent::__construct($service, $transporter, $packer, $dataFormatter, $pathGenerator);
    }
}
```

Now, you could use this class to call the remote method directly, like below:

```php
// Call the remote method `add` with arguments `1` and `2`.
// The $result is the result of the remote method.
$client = new CalculatorService();
$result = $client->add(1, 2);
```
