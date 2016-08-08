<?php


namespace SamIT\Proxy;


use React\EventLoop\LoopInterface;

/**
 * Connection class that implements a PortConnectionInterface.
 * @package SamIT\Proxy
 */
class PortConnection extends \React\Socket\Connection implements PortConnectionInterface
{
    /**
     * @var boolean Whether this is an incoming connection.
     */
    protected $incoming;

    public function __construct($stream, LoopInterface $loop, $incoming = false)
    {
        parent::__construct($stream, $loop);
        $this->incoming = $incoming;
    }


    /**
     * @return int The remote port for this connection.
     */
    public function getRemotePort()
    {
        return $this->parsePort(stream_socket_get_name($this->stream, true));
    }

    /**
     * @return int The target port for this connection.
     */
    public function getTargetPort()
    {
        return $this->parsePort(stream_socket_get_name($this->stream, !$this->incoming));
    }

    /**
     * @return string The target address for this connection.
     */
    public function getTargetAddress()
    {
        return $this->parseAddress(stream_socket_get_name($this->stream, !$this->incoming));
    }

    /**
     * @param $address
     * @return string
     * @todo Make parent function protected.
     */
    protected function parseAddress($address)
    {
        return trim(substr($address, 0, strrpos($address, ':')), '[]');
    }

    protected function parsePort($address)
    {
        $parts = explode(':', $address);
        return end($parts);
    }

    /**
     * @return int The source port for this connection.
     */
    public function getSourcePort()
    {
        return $this->incoming ? $this->getRemotePort() : $this->parsePort(stream_socket_get_name($this->stream, false));
    }

    /**
     * @return string The source address for this connection. This is the same as the remote address if this connection is incoming.
     */
    public function getSourceAddress()
    {
        return $this->incoming ? $this->getRemoteAddress() : $this->parseAddress(stream_socket_get_name($this->stream, false));
    }
}