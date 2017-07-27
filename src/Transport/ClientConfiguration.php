<?php
namespace SSH2\Transport;

use SSH2\Transport\KeyExchange\ClientConfiguration as KeyExchangeConfiguration;

class ClientConfiguration
{
    public function getSoftwareVersion(): string
    {

    }

    /**
     * @return null|string
     */
    public function getIdentificationStringComments(): ?string
    {

    }

    public function getClientIdentificationString(): string
    {
        $softwareVersion = $this->getSoftwareVersion();
        $comments = $this->getIdentificationStringComments();

        if (null === $comments || '' === $comments) {
            return "SSH-2.0-$softwareVersion\r\n";
        } else {
            return "SSH-2.0-$softwareVersion $comments\r\n";
        }
    }

    public function getKeyExchangeConfiguration(): KeyExchangeConfiguration
    {

    }
}
