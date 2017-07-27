<?php
namespace SSH2\Transport\KeyExchange\DiffieHellman;

use SSH2\Transport\Message\Message;

class KexdhReplyMessage extends Message
{
    private $serverPublicHostKeyAndCerts;
    private $f;
    private $signatureOfH;

    public function __construct(string $serverPublicHostKeyAndCerts, string $f, string $signatureOfH)
    {
        $this->serverPublicHostKeyAndCerts = $serverPublicHostKeyAndCerts;
        $this->f = $f;
        $this->signatureOfH = $signatureOfH;

        $binary = \pack('CNa*', DiffieHellmanMessageNumber::KEXDH_INIT, \strlen($e), $e);
        parent::__construct(DiffieHellmanMessageNumber::KEXDH_INIT, $binary);
    }

    public function getServerPublicHostKeyAndCerts(): string
    {
        return $this->serverPublicHostKeyAndCerts;
    }

    public function getF(): string
    {
        return $this->f;
    }

    public function getSignatureOfH()
    {
        return $this->signatureOfH;
    }
}
