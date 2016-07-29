<?php
namespace SamIT\Proxy;

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
    protected static $signatures = [
        2 => "\x0D\x0A\x0D\x0A\x00\x0D\x0A\x51\x55\x49\x54\x0A",
        1 => "PROXY"
    ];
    // 4 bits
    public $version = 2;
    // 4 bits
    public $command = self::CMD_PROXY;

    // 1 byte
    public $protocol = self::TCP4;

    protected static $lengths = [
        self::TCP4 => 12,
        self::UDP4 => 12,
        self::TCP6 => 36,
        self::UDP6 => 36,
        self::USTREAM => 216,
        self::USOCK => 216,
    ];

    /**
     * @var string The address of the client.
     */
    public $sourceAddress;

    /**
     * @var string The address to which the client connected.
     */
    public $targetAddress;

    /**
     * @var int The port of the client
     */
    public $sourcePort;

    /**
     * @var int The port to which the client connected.
     */
    public $targetPort;


    protected function getProtocol()
    {
        if ($this->version == 2) {
            return $this->protocol;
        } else {
            return array_flip((new \ReflectionClass($this))->getConstants())[$this->protocol];
        }
    }
    protected function getVersionCommand() {
        if ($this->version == 2) {
            return chr(($this->version << 4) + $this->command);
        }
    }
    /**
     * @return uint16_t
     */
    protected function getAddressLength()
    {
        if ($this->version == 2) {
            return pack('n', self::$lengths[$this->protocol]);
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

    protected static function decodeAddress($version, $address, $protocol)
    {
        if ($version == 1) {
            return $address;
        }
        switch ($protocol) {
            case self::TCP4:
            case self::UDP4:
            case self::TCP6:
            case self::UDP6:
                $result = inet_ntop($address);
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

    /**
     * @return string
     * @throws \Exception
     */
    protected function getAddresses()
    {
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

    /**
     * @return string
     * @throws \Exception
     */
    protected function getPorts()
    {
        return $this->encodePort($this->sourcePort, $this->protocol) . ($this->version == 1 ? " " : "") . $this->encodePort($this->targetPort, $this->protocol);
    }

    /**
     * @return string
     */
    protected function getSignature()
    {
        return self::$signatures[$this->version];
    }

    /**
     * Constructs the header by concatenating all relevant fields.
     * @return string
     */
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
     * This function creates the forwarding header. This header should be sent over the upstream connection as soon as
     * it is established.
     * @param string $sourceAddress
     * @param int $sourcePort
     * @param string $targetAddress
     * @param int $targetPort
     * @return StreamInterface
     * @throws \Exception
     */
    public static function createForward4($sourceAddress, $sourcePort, $targetAddress, $targetPort) {
        $result = new static();
        $result->version = 2;
        $result->sourceAddress = $sourceAddress;
        $result->targetPort = $targetPort;
        $result->targetAddress = $targetAddress;
        $result->sourcePort = $sourcePort;
        return $result;
    }

    /**
     * @param string $data
     * @return Header|null
     */
    public static function parseHeader(&$data)
    {
        foreach(self::$signatures as $version => $signature) {
            // Match.
            if (strncmp($data, $signature, strlen($signature)) === 0) {
                if ($version === 1) {
                    $result = self::parseVersion1($data);
                    break;
                } elseif ($version === 2) {
                    $result = self::parseVersion2($data);
                    break;
                }
            }
        }
        if (isset($result)) {
            $constructed = $result->constructProxyHeader();
            if (strncmp($constructed, $data, strlen($constructed)) === 0) {
                $data = substr($data, strlen($constructed));
                return $result;
            }
        }
    }

    protected static function parseVersion1($data)
    {
        $parts = explode("\x20", $data);
        if (count($parts) === 7 && $parts[6] === "\r\n") {
            $result = new Header();
            $result->version = 1;
            $result->protocol = $parts[1];
            $result->sourceAddress = $parts[2];
            $result->targetAddress = $parts[3];
            $result->sourcePort = $parts[4];
            $result->targetPort = $parts[5];
            return $result;
        }
    }

    protected static function parseVersion2($data)
    {
        $version = ord(substr($data, 12, 1)) >> 4;
        $command = ord(substr($data, 12, 1)) % 16;
        $protocol = substr($data, 13, 1);

        $pos = 16;
        $sourceAddress = self::decodeAddress($version, substr($data, $pos, self::$lengths[$protocol] / 2  - 2), $protocol);
        $pos += self::$lengths[$protocol] / 2  - 2;
        $targetAddress = self::decodeAddress($version, substr($data, $pos, self::$lengths[$protocol] / 2  - 2), $protocol);
        $pos += self::$lengths[$protocol] / 2  - 2;
        $sourcePort = unpack('n', substr($data, $pos, 2))[1];
        $targetPort = unpack('n', substr($data, $pos + 2, 2))[1];

        $result = new Header();
        $result->version = 2;
        $result->command = $command;
        $result->protocol = $protocol;
        $result->sourceAddress = $sourceAddress;
        $result->targetAddress = $targetAddress;
        $result->sourcePort = $sourcePort;
        $result->targetPort = $targetPort;
        return $result;
    }

}
