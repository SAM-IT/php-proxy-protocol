# php-proxy-protocol
Implementation of HAProxy v1 and v2 

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SAM-IT/php-proxy-protocol/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/SAM-IT/php-proxy-protocol/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/SAM-IT/php-proxy-protocol/badges/build.png?b=master)](https://scrutinizer-ci.com/g/SAM-IT/php-proxy-protocol/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/SAM-IT/php-proxy-protocol/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/SAM-IT/php-proxy-protocol/?branch=master)

# Example of forwarding requests to NGINX
This example illustrates how to create a reverse proxy server that forwards all requests to an NGINX server.

## Test script
````
<?php

include 'vendor/autoload.php';
use \React\SocketClient\TcpConnector;

/** @var \React\EventLoop\LoopInterface $loop */
$loop = \React\EventLoop\Factory::create();
$connector = new TcpConnector($loop);

/** @var \React\Socket\ServerInterface $servers */
$servers = [];

$server = $servers[] = new \React\Socket\Server($loop);
$server->listen(10000, '0.0.0.0');

$forwarder = new \SamIT\Proxy\Forwarder($connector);
$forwarder->on('forward', function(\React\Stream\ReadableStreamInterface $in,  \React\Stream\ReadableStreamInterface $out) {
    echo "Connection set up.\n";
});

$forwarder->forwardAll($server, '192.168.37.2', 8011, 1);
echo "Listening..\n";


$loop->run();
die("Done with loop\n");
````

## Configuration for nginx:
````
server {
    listen 8011 proxy_protocol;

    set_real_ip_from 127.0.0.1/24;
    
    real_ip_header proxy_protocol;
    add_header Content-Type text/plain;
    return 200 "${remote_addr}:${remote_port}\n"; 
}
````
