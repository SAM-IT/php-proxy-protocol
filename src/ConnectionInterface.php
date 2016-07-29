<?php


namespace SamIT\Proxy;


interface ConnectionInterface extends \React\Socket\ConnectionInterface
{
    /**
     * @return int The remote port for this connection.
     */
    public function getRemotePort();

}