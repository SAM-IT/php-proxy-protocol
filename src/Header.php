<?php
namespace SamIT\Proxy;


use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use React\ChildProcess\Process;

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
    public $signatures = [
        2 => "\x0D\x0A\x0D\x0A\x00\x0D\x0A\x51\x55\x49\x54\x0A",
        1 => "PROXY"
    ];
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


    protected function getProtocol() {
        if ($this->version == 2) {
            return $this->protocol;
        } else {
            return array_flip((new \ReflectionClass($this))->getConstants())[$this->protocol];
        }
    }
    protected function getVersionCommand() {
        if ($this->version == 2) {
            return chr($this->version * 2 ^ 5 + $this->command);
        }
    }
    /**
     * @return uint16_t
     */
    protected function getAddressLength()
    {
        if ($this->version == 2) {
            return pack('n', $this->lengths[$this->protocol]);
        }

    }

    protected function encodeAddress($address, $protocol) {
        if ($this->version == 1) {
            return $address;
        }
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
        return $this->encodeAddress($this->sourceAddress, $this->protocol) . ($this->version == 1 ? " " : "") .$this->encodeAddress($this->targetAddress, $this->protocol);
    }

    protected function encodePort($port, $protocol) {
        if ($this->version == 1) {
            return $port;
        }
        switch ($protocol) {
            case self::TCP4:
            case self::UDP4:
            case self::TCP6:
            case self::UDP6:
                $result = pack("n", $port);
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
        return $this->encodePort($this->sourcePort, $this->protocol) . ($this->version == 1 ? " " : "") . $this->encodePort($this->targetPort, $this->protocol);
    }

    protected function getSignature() {
        return $this->signatures[$this->version];
    }
    public function constructProxyHeader() {
        return implode($this->version == 1 ? "\x20" : "", array_filter([
            $this->getSignature(),
            $this->getVersionCommand(),
            $this->getProtocol(),
            $this->getAddressLength(),
            $this->getAddresses(),
            $this->getPorts(),
            $this->version == 1 ? "\r\n" : null
        ]));
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
        $result->version = 1;
        $result->sourceAddress = $sourceAddress;
        $result->targetPort = $targetPort;
        $result->targetAddress = $targetAddress;
        $result->sourcePort = $sourcePort;
        return $result;
    }

}
