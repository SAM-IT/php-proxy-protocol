<?php


namespace SamIT\Proxy;


use React\Dns\Resolver\Factory;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;

class Server extends \React\Socket\Server
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
     * @param $socket
     * @return Connection
     */
    public function createConnection($socket)
    {
        $result = new Connection($socket, $this->loop);
        $this->forwarder->forward($result, $this->forwardAddress, $this->forwardPort);
    }
}