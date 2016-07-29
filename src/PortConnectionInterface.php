<?php


namespace SamIT\Proxy;


interface PortConnectionInterface extends \React\Socket\ConnectionInterface
{
    /**
     * @return int The remote port for this connection.
     */
    public function getRemotePort();

    /**
     * @return int The target port for this connection.
     */
    public function getTargetPort();

    /**
     * @return string The target address for this connection.
     */
    public function getTargetAddress();

    /**
     * @return int The source port for this connection.
     */
    public function getSourcePort();

    /**
     * @return string The source address for this connection.
     */
    public function getSourceAddress();
}