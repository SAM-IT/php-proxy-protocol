<?php

namespace SamIT\Proxy;

use React\Promise\Promise;
use React\Socket\Connection;
use React\SocketClient\TcpConnector;
use React\Stream\Stream;

/**
 * Class Connection
 * Implements a connection where the remote end thinks it is talking to another server.
 * @package SamIT\Proxy
 */
class Forwarder
{
    protected $connector;

    public function __construct(TcpConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Forwards a connection to the specified host / port using the proxy protocol.
     * @param ProxyConnection $connection
     * @param string $forwardAddress The host to forward to
     * @param int $forwardPort The port to forward to
     * @return Promise
     */
    public function forward(Connection $connection, $forwardAddress, $forwardPort)
    {
        list($sourceAddress, $sourcePort) = explode(':', stream_socket_get_name($connection->stream, true));
        list($targetAddress, $targetPort) = explode(':', stream_socket_get_name($connection->stream, false));
        $header = Header::createForward4($sourceAddress, $sourcePort, $targetAddress, $targetPort);
        return $this->connector->create($forwardAddress, $forwardPort)
            ->then(function(Stream $forwardedConnection) use (
                $connection, $header,
                $sourceAddress, $sourcePort,
                $targetAddress, $targetPort
            ) {
                    $forwardedConnection->getBuffer()->once('full-drain', function() use ($connection, $forwardedConnection) {
                        $connection->pipe($forwardedConnection);
                        $forwardedConnection->pipe($connection);
                        $forwardedConnection->emit('init', [$forwardedConnection]);
                    });
                    $forwardedConnection->write($header);
                return $forwardedConnection;
        });
    }

}