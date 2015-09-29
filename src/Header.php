<?php
namespace samit\proxy;


use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;

class Header
{
    const CMD_PROXY = 1;
    const CMD_LOCAL = 0;
    const UNSPECIFIED_PROTOCOL = "\x00";
    const TCP4 = "\x11";
    const UDP4 = "\x12";
    const TCP6 = "\x21";
    const UDP6 = "\x22";
    const USTREAM = "\x31";
    const USOCK = "\x32";

    // 12 bytes.
    public $signature = "\x0D\x0A\x0D\x0A\x00\x0D\x0A\x51\x55\x49\x54\x0A";

    // 4 bits
    public $version = 2;
    // 4 bits
    public $command = self::CMD_PROXY;

    // 1 byte
    public $protocol = self::TCP4;

    protected $lengths = [
        self::TCP4 => 12,
        self::UDP4 => 12,
        self::TCP6 => 36,
        self::UDP6 => 36,
        self::USTREAM => 216,
        self::USOCK => 216,
    ];


    public $sourceAddress;
    public $targetAddress;
    public $sourcePort;
    public $targetPort;



    protected function getVersionCommand() {
        return chr($this->version * 2^5 + $this->command);
    }
    /**
     * @return uint16_t
     */
    protected function getAddressLength() {
        return pack($this->lengths[$this->protocol], 'n');
    }

    protected function encodeAddress($address, $protocol) {
        switch ($protocol) {
            case self::TCP4:
            case self::UDP4:
            case self::TCP6:
            case self::UDP6:
                $result = inet_pton($address);
                break;
            case self::USTREAM:
            case self::USOCK:
                throw new \Exception("Unix socket not (yet) supported.");
                break;
            default:
                throw new \UnexpectedValueException("Invalid protocol.");

        }
        return $result;
    }
    protected function getAddresses() {
        return $this->encodeAddress($this->sourceAddress, $this->protocol) . $this->encodeAddress($this->targetAddress, $this->protocol);
    }

    protected function encodePort($port, $protocol) {
        switch ($protocol) {
            case self::TCP4:
            case self::UDP4:
            case self::TCP6:
            case self::UDP6:
                $result = pack($port, "n");
                break;
            case self::USTREAM:
            case self::USOCK:
                throw new \Exception("Unix socket not (yet) supported.");
                break;
            default:
                throw new \UnexpectedValueException("Invalid protocol.");

        }
        return $result;
    }

    protected function getPorts() {
        return $this->encodePort($this->sourcePort, $this->protocol) . $this->encodePort($this->targetPort, $this->protocol);
    }
    public function constructProxyHeader() {
        return implode('', [
            // 12 bytes
            $this->signature,
            // 1 byte
            $this->getVersionCommand(),
            // 1 byte
            $this->protocol,
            // 2 bytes
            $this->getAddressLength(),
            $this->getAddresses(),
            $this->getPorts()
        ]);
    }

    public function __toString()
    {
        return $this->constructProxyHeader();
    }

    /**
     * @param $socket
     * @param $targetAddress
     * @param $targetPort
     * @return StreamInterface
     * @throws \Exception
     */
    public static function createForward4($sourceAddress, $sourcePort, $targetAddress, $targetPort) {
        $result = new static();
        $result->sourceAddress = $sourceAddress;
        $result->targetPort = $targetPort;
        $result->targetAddress = $targetAddress;
        $result->sourcePort = $sourcePort;
        return $result;
    }

}