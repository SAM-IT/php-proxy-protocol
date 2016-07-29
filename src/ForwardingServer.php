<?php


namespace SamIT\Proxy;


use React\Dns\Resolver\Factory;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Stream\Stream;

/**
 * Class ForwardingServer
 * A server that forwards connections.
 * It will accept normal connections.
 * @package SamIT\Proxy
 */
class ForwardingServer extends \React\Socket\Server
{
    private $loop;
    /**
     * @var Forwarder
     */
    private $forwarder;

    private $forwardAddress;
    private $forwardPort;

    public function __construct(LoopInterface $loop, Resolver $resolver, $forwardAddress, $forwardPort)
    {
        $this->loop = $loop;
        $this->forwardAddress = $forwardAddress;
        $this->forwardPort = $forwardPort;
        parent::__construct($loop);
        $this->forwarder = new Forwarder($loop, $resolver);
    }

    /**
     * Handle the connection. We only emit the connection event after the proper header was received.
     * @param $socket
     */
    public function handleConnection($socket)
    {
        stream_set_blocking($socket, 0);
        $incoming = $this->createConnection($socket);

        $this->forwarder->forward($incoming, $this->forwardAddress, $this->forwardPort)->then(function(Stream $outgoing) use ($incoming) {
            $outgoing->on('init', function() use ($outgoing, $incoming) {
               $this->emit('connection', [$incoming, $this->createConnection($outgoing->stream, false)]);
            });
        });


    }


    /**
     * @param $socket
     * @return ProxyConnection
     */
    public function createConnection($socket, $incoming = true)
    {
        $result = new PortConnection($socket, $this->loop, $incoming);
        return $result;
    }
}