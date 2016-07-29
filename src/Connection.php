<?php


namespace SamIT\Proxy;


use React\EventLoop\LoopInterface;

/**
 * This class wraps an existing connection and add support for the proxy protocol.
 * @package SamIT\Proxy
 */
class Connection extends \React\Socket\Connection implements ConnectionInterface
{

    /**
     * @var Header;
     */
    protected $header;

    public function __construct($stream, LoopInterface $loop)
    {
        parent::__construct($stream, $loop);
    }

    public function handleData($stream)
    {
        // Socket is raw, not using fread as it's interceptable by filters
        // See issues #192, #209, and #240
        $data = stream_socket_recvfrom($stream, $this->bufferSize);
        if ('' !== $data && false !== $data) {
            if (!isset($this->header)) {
                $data = $this->readProxyHeader($data);
            }
            if ('' !== $data && false !== $data) {
                $this->emit('data', array($data, $this));
            }
        } elseif ('' === $data || false === $data || feof($stream)) {
            $this->end();
        }
    }

    public function readProxyHeader($data)
    {
        // Read the data, emit the rest.
        $header = Header::parseHeader($data);
        if ($header instanceof Header) {
            $this->header = $header;
        } else {
            $this->header = false;
        }
        return $data;
    }


    public function getRemoteAddress()
    {
        return $this->header->sourceAddress;
    }

    /**
     * @return int The remote port for this connection.
     */
    public function getRemotePort()
    {
        return $this->header->sourcePort;
    }
}