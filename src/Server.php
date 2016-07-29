<?php


namespace SamIT\Proxy;


use React\Dns\Resolver\Factory;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;

/**
 * Class Server
 * A server that receives proxy connections.
 * If no proxy header is received within 5 seconds after connection opening than the connection is closed.
 * The `connection` event is only triggered after receiving the header.
 * @package SamIT\Proxy
 */
class Server extends \React\Socket\Server
{
    private $loop;

    public function __construct(LoopInterface $loop, Resolver $resolver)
    {
        $this->loop = $loop;
        parent::__construct($loop);
    }

    /**
     * Handle the connection. We only emit the connection event after the proper header was received.
     * @param $socket
     */
    public function handleConnection($socket)
    {
        stream_set_blocking($socket, 0);
        $client = $this->createConnection($socket);

        $timer = $this->loop->addTimer(5, function() use ($client) {
            $client->removeAllListeners('init');
            $client->end('Timeout waiting for PROXY header.');
            $this->emit('proxytimeout', [$client]);
        });

        $client->on('init', function($connection) use ($client, $timer) {
            $timer->cancel();
            $this->emit('connection', [$connection]);
        });
    }

    /**
     * @param $socket
     * @return ProxyConnection
     */
    public function createConnection($socket)
    {
        return new ProxyConnection($socket, $this->loop);
    }
}