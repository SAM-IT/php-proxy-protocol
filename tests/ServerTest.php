<?php


namespace SamIT\Tests\Proxy;


use React\Dns\Resolver\Factory;
use React\Dns\Resolver\Resolver;
use React\EventLoop\LoopInterface;
use React\SocketClient\TcpConnector;
use SamIT\Proxy\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public $testHost = '0.0.0.0';
    public $testPort = 10000;
    public function testConstructor()
    {
        $server = new Server($this->createLoopMock(), $this->createResolverMock());

        $this->assertInstanceOf(Server::class, $server);
    }

    private function createResolverMock()
    {
        return $this->createMock(Resolver::class);
    }
    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }

    public function testListen()
    {
        $server = new Server($this->createLoopMock(), $this->createResolverMock());
        $server->listen($this->testPort, $this->testHost);
        $this->assertEquals($this->testPort, $server->getPort());
    }

    public function testTimeout()
    {
        /** @var LoopInterface $loop */
        $loop = \React\EventLoop\Factory::create();
        $server = new Server($loop, $this->createResolverMock());
        $server->listen($this->testPort, $this->testHost);


        $timeoutOccurred = false;
        $connected = false;
        $connector = new TcpConnector($loop);
        $connector->create($this->testHost, $this->testPort)->then(function() use (&$connected) {
            $connected = true;
        });
        $timer = $loop->addTimer(10, function() use ($server) {
            $server->shutdown();
        });

        $server->on('proxytimeout', function() use ($server, &$timeoutOccurred, $timer) {
            $timeoutOccurred = true;
            $server->shutdown();
            $timer->cancel();
        });
        $loop->run();
        $this->assertTrue($connected, "Failed to connect to server.");
        $this->assertTrue($timeoutOccurred, "No timeout occurred.");


//        $this->assertEquals(10000, $server->getPort());
    }
}
