<?php


namespace SamIT\Tests\Proxy;


use React\Dns\Resolver\Resolver;
use SamIT\Proxy\Forwarder;
use SamIT\Proxy\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loop   = $this->createLoopMock();
        $resolver = $this->createMock(Resolver::class);
        $server = new Server($loop, $resolver, '127.0.0.1', 10000);

        $this->assertInstanceOf(Server::class, $server);
    }

    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }
}
