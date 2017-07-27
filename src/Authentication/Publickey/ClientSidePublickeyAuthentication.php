<?php
namespace SSH2\Authentication\Publickey;

class ClientSidePublickeyAuthentication
{
    const METHOD_NAME = 'publickey';

    private $privateKey;
    private $publicKey;

    public function __construct(string $privateKey, string $publicKey)
    {
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
    }
}
