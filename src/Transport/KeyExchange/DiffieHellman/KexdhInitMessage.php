<?php
namespace SSH2\Transport\KeyExchange\DiffieHellman;

use SSH2\Transport\Message\Message;

class KexdhInitMessage extends Message
{
    private $e;

    public function __construct(string $e)
    {
        $this->e = $e;

        $binary = \pack('CNa*', DiffieHellmanMessageNumber::KEXDH_INIT, \strlen($e), $e);
        parent::__construct(DiffieHellmanMessageNumber::KEXDH_INIT, $binary);
    }

    public function getE(): string
    {
        return $this->e;
    }
}
