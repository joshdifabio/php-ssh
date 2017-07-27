<?php
namespace SSH2\Authentication\Message;

use SSH2\Transport\Message\Message;

class UserauthRequestMessage extends Message
{
    public static function toCheckPublicKeyMethodAcceptable(
        string $userName,
        string $serviceName,
        string $publicKeyAlgorithm,
        string $publicKey
    ): UserauthRequestMessage {

    }

    public static function withPublicKeyMethod(
        string $userName,
        string $serviceName,
        string $publicKeyAlgorithm,
        string $publicKey,
        string $signature
    ): UserauthRequestMessage {

    }

    public static function withPasswordMethod(): UserauthRequestMessage
    {

    }
}
