<?php


namespace SamIT\Proxy;


use React\EventLoop\LoopInterface;

/**
 * This class wraps an existing connection and add support for the proxy protocol.
 * @package SamIT\Proxy
 */
class ProxyConnection extends PortConnection implements PortConnectionInterface
{

    /**
     * @var Header;
     */
    protected $header;

    public function __construct($stream, LoopInterface $loop)
    {
        parent::__construct($stream, $loop, true);
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
            $this->emit('init', [$this]);
        } else {
            $this->header = false;
        }
        return $data;
    }

    public function getHeader()
    {
        if (!isset($this->header)) {
            throw new \RuntimeException("Cannot use connection until a proxy header has been received.");
        }
        return $this->header;
    }

    public function getRemoteAddress()
    {
        return $this->getHeader()->sourceAddress;
    }

    /**
     * @return int The remote port for this connection.
     */
    public function getRemotePort()
    {
        return $this->getHeader()->sourcePort;
    }

    /**
     * @return int The target port for this connection.
     */
    public function getTargetPort()
    {
        return $this->getHeader()->targetPort;
    }

    /**
     * @return string The target address for this connection.
     */
    public function getTargetAddress()
    {
        return $this->getHeader()->targetAddress;
    }
}