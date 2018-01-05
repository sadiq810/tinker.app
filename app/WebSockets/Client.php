<?php

namespace App\WebSockets;

use App\Services\Docker\Container;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use PartyLine;

class Client
{
    /** @var \App\Services\Docker\Container */
    protected $container;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var \Ratchet\ConnectionInterface */
    protected $connection;

    public function __construct(ConnectionInterface $connection, LoopInterface $loop)
    {
        $this->connection = $connection;

        $this->loop = $loop;
    }

    public function attachContainer(Container $container): self
    {
        $this->container = $container;

        $this->container->onMessage(function ($message) {
            $this->connection->send((string) $message);
        });

        $this->container->onClose(function ($message) {
            PartyLine::error("Connection to container lost; closing websocket to client {$this->connection->resourceId}");

            $this->connection->close();
        });

        return $this;
    }

    public function cleanupContainer()
    {
        $this
            ->container
            ->stop()
            ->remove();
    }

    public function sendToTinker(string $message)
    {
        $this->container->sendToWebSocket($message);
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}
