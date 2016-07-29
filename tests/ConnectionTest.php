<?php


namespace SamIT\Tests\Proxy;


use SamIT\Proxy\Connection;
use React\Socket\Server;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loop   = $this->createLoopMock();
        $server = new Server($loop);

        $server->listen(0);

        $class  = new \ReflectionClass('React\\Socket\\Server');
        $master = $class->getProperty('master');
        $master->setAccessible(true);

        $servConn = new Connection($server->master, $loop);

        $this->assertInstanceOf(Connection::class, $servConn);
    }

    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }
}
