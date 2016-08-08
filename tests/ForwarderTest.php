<?php
namespace SamIT\Tests\Proxy;

use React\SocketClient\TcpConnector;
use SamIT\Proxy\Forwarder;

class ForwarderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loop   = $this->createLoopMock();
        $connector = new TcpConnector($loop);
        $forwarder = new Forwarder($connector);
        $this->assertInstanceOf(Forwarder::class, $forwarder);
    }

    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }
}
