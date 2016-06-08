<?php


namespace SamIT\Proxy;


use React\EventLoop\LoopInterface;

class Server extends \React\Socket\Server
{
    private $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        parent::__construct($loop);
    }

    public function createConnection($socket)
    {
        return new Connection($socket, $this->loop);
    }

}