<?php

namespace SamIT\Proxy;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use React\Promise\Promise;
use React\Socket\Connection;
use React\Socket\ConnectionInterface;
use React\Socket\ServerInterface;
use React\SocketClient\TcpConnector;
use React\Stream\Stream;

/**
 * Class Forwarder
 * Implements a connection where the remote end thinks it is talking to another server.
 * @package SamIT\Proxy
 */
class Forwarder implements EventEmitterInterface
{
    use EventEmitterTrait;
    protected $connector;

    public function __construct(TcpConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Forwards a connection to the specified host / port using the proxy protocol.
     * @param ConnectionInterface $connection
     * @param string $forwardAddress The host to forward to
     * @param int $forwardPort The port to forward to
     * @return Promise
     */
    public function forward(ConnectionInterface $connection, $forwardAddress, $forwardPort, $protocolVersion = 2)
    {
        if ($connection instanceof PortConnection) {
            $sourceAddress = $connection->getSourceAddress();
            $sourcePort = $connection->getSourcePort();
            $targetAddress = $connection->getTargetAddress();
            $targetPort = $connection->getTargetPort();
        } elseif ($connection instanceof Connection) {
            list($sourceAddress, $sourcePort) = explode(':', stream_socket_get_name($connection->stream, true));
            list($targetAddress, $targetPort) = explode(':', stream_socket_get_name($connection->stream, false));
        } else {
            throw new \InvalidArgumentException("This connection type is not supported.");
        }

        $connection->pause();

        $header = Header::createForward4($sourceAddress, $sourcePort, $targetAddress, $targetPort, $protocolVersion);
        /** @var Promise $promise */
        $promise = $this->connector->create($forwardAddress, $forwardPort);
        return $promise
            ->then(function(Stream $forwardedConnection) use (
                $connection, $header,
                $sourceAddress, $sourcePort,
                $targetAddress, $targetPort
            ) {
                $forwardedConnection->pause();
                $forwardedConnection->getBuffer()->once('full-drain', function() use ($connection, $forwardedConnection) {
                    $this->emit('forward', [$connection, $forwardedConnection]);
                    $connection->pipe($forwardedConnection);
                    $forwardedConnection->pipe($connection);
                    $connection->resume();
                    $forwardedConnection->resume();
                });
                $forwardedConnection->write($header);

        });
    }

    public function forwardAll(ServerInterface $server, $forwardAddress, $forwardPort, $protocolVersion = 2)
    {
        $server->on('connection', function(ConnectionInterface $connection) use ($forwardAddress, $forwardPort, $protocolVersion) {
            $this->forward($connection, $forwardAddress, $forwardPort, $protocolVersion);
        });
    }

}