<?php


namespace SamIT\Tests\Proxy;


use React\Dns\Resolver\Resolver;
use SamIT\Proxy\Forwarder;

class ForwarderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $loop   = $this->createLoopMock();
        $resolver = $this->createMock(Resolver::class);
        $forwarder = new Forwarder($loop, $resolver);

        $this->assertInstanceOf(Forwarder::class, $forwarder);
    }

    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }
}
