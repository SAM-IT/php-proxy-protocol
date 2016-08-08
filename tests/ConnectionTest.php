<?php


namespace SamIT\Tests\Proxy;


use SamIT\Proxy\ProxyConnection;
use React\Socket\Server;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loop   = $this->createLoopMock();
        $server = new Server($loop);

        $server->listen(0);

        $class  = new \ReflectionClass(\React\Socket\Server::class);
        $master = $class->getProperty('master');
        $master->setAccessible(true);

        $servConn = new ProxyConnection($server->master, $loop);

        $this->assertInstanceOf(ProxyConnection::class, $servConn);
    }

    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }
}
