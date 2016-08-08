<?php
namespace SamIT\Tests\Proxy;

use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\SocketClient\TcpConnector;
use React\Stream\Stream;
use SamIT\Proxy\Header;
use SamIT\Proxy\ProxyConnection;
use SamIT\Proxy\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public $testHost = '127.0.0.1';
    public $testPort = 10000;
    public function testConstructor()
    {
        $server = new Server($this->createLoopMock());
        $this->assertInstanceOf(Server::class, $server);
    }

    private function createLoopMock()
    {
        return $this->createMock(\React\EventLoop\LoopInterface::class);
    }

    public function testListen()
    {
        $server = new Server($this->createLoopMock());
        $server->listen($this->testPort, $this->testHost);
        $this->assertEquals($this->testPort, $server->getPort());
    }

    public function testTimeout()
    {
        /** @var LoopInterface $loop */
        $loop = \React\EventLoop\Factory::create();
        $server = new Server($loop);
        $server->listen($this->testPort, $this->testHost);

        $mock = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['proxyTimeout', 'connectionException', 'timeout', 'connected'])
            ->getMock();
        $mock->expects($this->never())->method('connectionException');
        $mock->expects($this->never())->method('timeout');
        $mock->expects($this->once())->method('proxyTimeout');
        $mock->expects($this->once())->method('connected');

        $timer = $loop->addTimer(10, function() use ($server, $mock) {
            $server->shutdown();
            $mock->timeout();
        });

        $connector = new TcpConnector($loop);
        $connector->create($this->testHost, $this->testPort)->done(function() use ($timer, $mock) {
            $mock->connected();
        }, function() use ($mock, $timer, $server) {
            $timer->cancel();
            $server->shutdown();
            $mock->connectionException();
        });

        $server->on('proxytimeout', [$mock, 'proxyTimeout']);
        $server->on('proxytimeout', function() use ($server, $timer) {
            $server->shutdown();
            $timer->cancel();
        });
        $loop->run();
    }

    public function testConnection()
    {
        /** @var LoopInterface $loop */
        $loop = \React\EventLoop\Factory::create();
        $server = new Server($loop);
        $server->listen($this->testPort, $this->testHost);

        $mock = $this->getMockBuilder(\stdClass::class)
            ->setMethods([
                'proxyTimeout',
                'connectionException',
                'timeout',
                'connection'
            ])
            ->getMock();
        $mock->expects($this->never())->method('connectionException');
        $mock->expects($this->never())->method('timeout');
        $mock->expects($this->never())->method('proxyTimeout');
        $mock->expects($this->once())->method('connection');

        $timer = $loop->addTimer(10, function() use ($server, $mock) {
            $server->shutdown();
            $mock->timeout();
        });

        $shutdown = function() use ($timer, $server) {
            $server->shutdown();
            $timer->cancel();
        };

        $connector = new TcpConnector($loop);
        $connector->create($this->testHost, $this->testPort)->done(function(Stream $stream) use ($timer) {
            $header = Header::createForward4('127.0.0.254', 1234, '127.0.0.12', 1235);
            $stream->write($header);
        }, function() use ($mock, $shutdown) {
            $shutdown();
            $mock->connectionException();
        });

        $server->on('proxytimeout', [$mock, 'proxyTimeout']);
        $server->on('proxytimeout', $shutdown);
        $server->on('connection', function(ProxyConnection $connection) use ($mock) {
            $this->assertEquals('127.0.0.254', $connection->getSourceAddress());
            $this->assertEquals('127.0.0.254', $connection->getRemoteAddress());

            $this->assertEquals('127.0.0.12', $connection->getTargetAddress());

            $this->assertEquals(1235, $connection->getTargetPort());
            $this->assertEquals(1234, $connection->getSourcePort());
            $connection->close();
            $mock->connection();
        });
        $server->on('connection', $shutdown);
        $loop->run();
    }
}
