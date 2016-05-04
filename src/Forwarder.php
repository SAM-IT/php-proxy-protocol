<?php

namespace SamIT\Proxy;

use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Socket\Connection;
use React\Socket\ConnectionInterface;
use React\Stream\Stream;

/**
 * Class Connection
 * Implements a connection where the remote end thinks it is talking to another server.
 * @package SamIT\Proxy
 */
class Forwarder
{
    protected $connector;
    protected $loop;
    public function __construct(
        LoopInterface $loop,
        Resolver $resolver
    )
    {
        $this->loop = $loop;
//        $this->connector =
    }

    public function forward(Connection $connection = null, $forwardAddress, $forwardPort)
    {
        list($sourceAddress, $sourcePort) = explode(':', stream_socket_get_name($connection->stream, true));
        list($targetAddress, $targetPort) = explode(':', stream_socket_get_name($connection->stream, false));
//        var_dump("tcp://$forwardAddress:$forwardPort");
//        $client = stream_socket_client("tcp://$forwardAddress:$forwardPort");
//        $forwardedConnection = new Stream($client, $this->loop);
        $header = Header::createForward4($sourceAddress, $sourcePort, $targetAddress, $targetPort);
//        $forwardedConnection->write($header);
//
//        $connection->pipe($forwardedConnection);
//        $forwardedConnection->pipe($connection);

        return $this->connector
            ->create($forwardAddress, $forwardPort)
            ->then(function(ConnectionInterface $forwardedConnection) use (
                $connection, $header,
                $sourceAddress, $sourcePort,
                $targetAddress, $targetPort
            ) {
                $forwardedConnection->write($header);
                $connection->pipe($forwardedConnection);
                $forwardedConnection->pipe($connection);
        });
    }

}