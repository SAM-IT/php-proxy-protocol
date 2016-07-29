<?php


namespace SamIT\Tests\Proxy;


use SamIT\Proxy\Header;

class HeaderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $header = Header::createForward4('127.0.0.1', 10000, '127.0.0.1', 10001);
        $this->assertInstanceOf(Header::class, $header);
    }


}
