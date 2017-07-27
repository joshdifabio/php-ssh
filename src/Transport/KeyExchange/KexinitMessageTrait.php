<?php
namespace SSH2\Transport\KeyExchange;

trait KexinitMessageTrait
{
    private $kexAlgorithms = ['none'];
    private $serverHostKeyAlgorithms = ['none'];
    private $encryptionAlgorithmsClientToServer = ['none'];
    private $encryptionAlgorithmsServerToClient = ['none'];
    private $macAlgorithmsClientToServer = ['none'];
    private $macAlgorithmsServerToClient = ['none'];
    private $compressionAlgorithmsClientToServer = ['none'];
    private $compressionAlgorithmsServerToClient = ['none'];
    private $languagesClientToServer = [];
    private $languagesServerToClient = [];
    private $firstKexPacketFollows = false;

    public function getKexAlgorithms(): array
    {
        return $this->kexAlgorithms;
    }

    public function getServerHostKeyAlgorithms(): array
    {
        return $this->serverHostKeyAlgorithms;
    }

    public function getEncryptionAlgorithmsClientToServer(): array
    {
        return $this->encryptionAlgorithmsClientToServer;
    }

    public function getEncryptionAlgorithmsServerToClient(): array
    {
        return $this->encryptionAlgorithmsServerToClient;
    }

    public function getMacAlgorithmsClientToServer(): array
    {
        return $this->macAlgorithmsClientToServer;
    }

    public function getMacAlgorithmsServerToClient(): array
    {
        return $this->macAlgorithmsServerToClient;
    }

    public function getCompressionAlgorithmsClientToServer(): array
    {
        return $this->compressionAlgorithmsClientToServer;
    }

    public function getCompressionAlgorithmsServerToClient(): array
    {
        return $this->compressionAlgorithmsServerToClient;
    }

    public function getLanguagesClientToServer(): array
    {
        return $this->languagesClientToServer;
    }

    public function getLanguagesServerToClient(): array
    {
        return $this->languagesServerToClient;
    }

    public function getFirstKexPacketFollows(): bool
    {
        return $this->firstKexPacketFollows;
    }
}
